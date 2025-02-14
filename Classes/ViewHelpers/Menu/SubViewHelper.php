<?php
namespace FluidTYPO3\Vhs\ViewHelpers\Menu;

/*
 * This file is part of the FluidTYPO3/Vhs project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

/**
 * ### Page: Auto Sub Menu ViewHelper
 *
 * Recycles the parent menu ViewHelper instance, resetting the
 * page UID used as starting point and repeating rendering of
 * the exact same tag content.
 *
 * Used in custom menu rendering to indicate where a submenu is
 * to be rendered; accepts only a single argument called `pageUid`
 * which defines the new starting page UID that is used in the
 * recycled parent menu instance.
 */
class SubViewHelper extends AbstractMenuViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument(
            'pageUid',
            'mixed',
            'Page UID to be overridden in the recycled rendering of the parent instance, if one exists',
            true
        );
    }

    /**
     * @return null|string
     */
    public function render()
    {
        $pageUid = $this->arguments['pageUid'];
        $parentInstance = $this->retrieveReconfiguredParentMenuInstance($pageUid);
        if (!$parentInstance) {
            return null;
        }
        $parentArguments = $parentInstance->getMenuArguments();
        $isActive = $this->pageService->isActive($pageUid);
        // Note about next case: although $isCurrent in most cases implies $isActive, cases where the menu item
        // that is being rendered is in fact the current page but is NOT part of the rootline of the menu being
        // rendered - which is expected for example if using a page setting to render a different page in menus.
        // This means that the following check although it appears redundant, it is in fact not.
        $isCurrent = $this->pageService->isCurrent($pageUid);
        $isExpanded = (boolean) $parentArguments['expandAll'];
        $shouldRender = $isActive || $isCurrent || $isExpanded;
        if (!$shouldRender) {
            return null;
        }
        // retrieve the set of template variables which were in play when the parent menu VH started rendering.
        /** @var iterable $variables */
        $variables = $this->renderingContext->getViewHelperVariableContainer()->get(
            AbstractMenuViewHelper::class,
            'variables'
        );
        $parentInstance->setOriginal(false);
        $content = $parentInstance->render();
        // restore the previous set of variables after they most likely have changed during the render() above.
        $variableProvider = $this->renderingContext->getVariableProvider();
        foreach ($variables as $name => $value) {
            /** @var string $name */
            if ($variableProvider->exists($name)) {
                $variableProvider->remove($name);
                $variableProvider->add($name, $value);
            }
        }

        return $content;
    }
}
