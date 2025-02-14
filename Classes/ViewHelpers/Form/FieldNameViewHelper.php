<?php
namespace FluidTYPO3\Vhs\ViewHelpers\Form;

/*
 * This file is part of the FluidTYPO3/Vhs project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Form Field Name View Helper
 *
 * This viewhelper returns the properly prefixed name of the given
 * form field and generates the corresponding HMAC to allow posting
 * of dynamically added fields.
 */
class FieldNameViewHelper extends AbstractViewHelper
{
    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    public function injectPersistenceManager(PersistenceManagerInterface $persistenceManager): void
    {
        $this->persistenceManager = $persistenceManager;
    }

    public function initializeArguments(): void
    {
        $this->registerArgument('name', 'string', 'Name of the form field to generate the HMAC for.');
        $this->registerArgument(
            'property',
            'string',
            'Name of object property. If used in conjunction with <f:form object="...">, "name" argument will ' .
            'be ignored.'
        );
    }

    /**
     * @return string
     */
    public function render()
    {
        $viewHelperVariableContainer = $this->renderingContext->getViewHelperVariableContainer();
        if ($this->isObjectAccessorMode()) {
            $formObjectName = $viewHelperVariableContainer->get(FormViewHelper::class, 'formObjectName');
            if (!empty($formObjectName)) {
                $propertySegments = explode('.', $this->arguments['property']);
                $propertyPath = '';
                foreach ($propertySegments as $segment) {
                    $propertyPath .= '[' . $segment . ']';
                }
                $name = $formObjectName . $propertyPath;
            } else {
                $name = $this->arguments['property'];
            }
        } else {
            $name = $this->arguments['name'];
        }
        if (null === $name || '' === $name) {
            return '';
        }
        if (!$viewHelperVariableContainer->exists(FormViewHelper::class, 'fieldNamePrefix')) {
            return $name;
        }
        $fieldNamePrefix = $viewHelperVariableContainer->get(FormViewHelper::class, 'fieldNamePrefix');
        if (!is_string($fieldNamePrefix) || $fieldNamePrefix === '') {
            return $name;
        }
        $fieldNameSegments = explode('[', $name, 2);
        $name = $fieldNamePrefix . '[' . $fieldNameSegments[0] . ']';
        if (1 < count($fieldNameSegments)) {
            $name .= '[' . $fieldNameSegments[1];
        }

        if ($viewHelperVariableContainer->exists(FormViewHelper::class, 'formFieldNames')) {
            /** @var array $formFieldNames */
            $formFieldNames = $viewHelperVariableContainer->get(FormViewHelper::class, 'formFieldNames');
        } else {
            $formFieldNames = [];
        }
        $formFieldNames[] = $name;
        $viewHelperVariableContainer->addOrUpdate(FormViewHelper::class, 'formFieldNames', $formFieldNames);
        return $name;
    }

    protected function isObjectAccessorMode(): bool
    {
        return (
            $this->hasArgument('property')
            && $this->renderingContext->getViewHelperVariableContainer()->exists(
                FormViewHelper::class,
                'formObjectName'
            )
        );
    }
}
