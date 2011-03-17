<?php
	/*******************************************************************
	 *  Copyright notice
	 *
	 *  (c) 2011 Kai Vogel <kai.vogel@speedprogs.de>, Speedprogs.de
	 *
	 *  All rights reserved
	 *
	 *  This script is part of the TYPO3 project. The TYPO3 project is
	 *  free software; you can redistribute it and/or modify
	 *  it under the terms of the GNU General Public License as
	 *  published by the Free Software Foundation; either version 2 of
	 *  the License, or (at your option) any later version.
	 *
	 *  The GNU General Public License can be found at
	 *  http://www.gnu.org/copyleft/gpl.html.
	 *
	 *  This script is distributed in the hope that it will be useful,
	 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
	 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 *  GNU General Public License for more details.
	 *
	 *  This copyright notice MUST APPEAR in all copies of the script!
	 ******************************************************************/

	/**
	 * URL View Helper
	 *
	 * @version $Id$
	 * @copyright Copyright belongs to the respective authors
	 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
	 */
	class Tx_TerFe2_ViewHelpers_UrlViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

		/**
		 * Renders a URL to given controller and action with params
		 * @param string $action Target action
		 * @param array $arguments Arguments
		 * @param string $controller Target controller. If NULL current controllerName is used
		 * @param string $extensionName Target Extension Name (without "tx_" prefix and no underscores). If NULL the current extension name is used
		 * @param string $pluginName Target plugin. If empty, the current plugin name is used
		 * @param integer $pageUid target page. See TypoLink destination
		 * @param integer $pageType type of the target page. See typolink.parameter
		 * @param boolean $noCache set this to disable caching for the target page. You should not need this.
		 * @param boolean $noCacheHash set this to supress the cHash query parameter created by TypoLink. You should not need this.
		 * @param string $section the anchor to be added to the URI
		 * @param string $format The requested format, e.g. ".html"
		 * @param boolean $linkAccessRestrictedPages If set, links pointing to access restricted pages will still link to the page even though the page cannot be accessed.
		 * @param array $additionalParams additional query parameters that won't be prefixed like $arguments (overrule $arguments)
		 * @param boolean $absolute If set, the URI of the rendered link is absolute
		 * @param boolean $addQueryString If set, the current query parameters will be kept in the URI
		 * @param array $argumentsToBeExcludedFromQueryString arguments to be removed from the URI. Only active if $addQueryString = TRUE
		 * @param boolean $makeAbsolute Add host to URL
		 * @return string Rendered link
		 * @see Tx_Fluid_ViewHelpers_Link_ActionViewHelper::render
		 */
		public function render($action = NULL, array $arguments = array(), $controller = NULL, $extensionName = NULL, $pluginName = NULL, $pageUid = NULL, $pageType = 0, $noCache = FALSE, $noCacheHash = FALSE, $section = '', $format = '', $linkAccessRestrictedPages = FALSE, array $additionalParams = array(), $absolute = FALSE, $addQueryString = FALSE, array $argumentsToBeExcludedFromQueryString = array(), $makeAbsolute = FALSE) {
			$uriBuilder = $this->controllerContext->getUriBuilder();
			$uri = $uriBuilder
				->reset()
				->setTargetPageUid($pageUid)
				->setTargetPageType($pageType)
				->setNoCache($noCache)
				->setUseCacheHash(!$noCacheHash)
				->setSection($section)
				->setFormat($format)
				->setLinkAccessRestrictedPages($linkAccessRestrictedPages)
				->setArguments($additionalParams)
				->setCreateAbsoluteUri($absolute)
				->setAddQueryString($addQueryString)
				->setArgumentsToBeExcludedFromQueryString($argumentsToBeExcludedFromQueryString)
				->uriFor($action, $arguments, $controller, $extensionName, $pluginName);

			return ($makeAbsolute ? t3lib_div::locationHeaderUrl($uri) : $uri);
		}

	}
?>