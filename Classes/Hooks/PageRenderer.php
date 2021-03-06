<?php
namespace GridElementsTeam\Gridelements\Hooks;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Jo Hasenau <info@cybercraft.de>, Tobias Ferger <tobi@tt36.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
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
 ***************************************************************/

/**
 * Class/Function which adds the necessary ExtJS and pure JS stuff for the grid elements.
 *
 * @author		Jo Hasenau <info@cybercraft.de>, Tobias Ferger <tobi@tt36.de>
 * @package		TYPO3
 * @subpackage	tx_gridelements
 */
class PageRenderer {

	/**
	 * wrapper function called by hook (t3lib_pageRenderer->render-preProcess)
	 *
	 * @param	array	            $parameters: An array of available parameters
	 * @param	\TYPO3\CMS\Core\Page\PageRenderer $pageRenderer: The parent object that triggered this hook
	 * @return	void
	 */
	public function addJSCSS($parameters, &$pageRenderer) {
		if($GLOBALS['MCONF']['name'] === 'web_layout' || $GLOBALS['MCONF']['name'] === 'web_list') {
			$this->addJS($parameters, $pageRenderer);
			$this->addCSS($parameters, $pageRenderer);
		}
	}

	/**
	 * method that adds JS files within the page renderer
	 *
	 * @param	array	            $parameters: An array of available parameters while adding JS to the page renderer
	 * @param	\TYPO3\CMS\Core\Page\PageRenderer $pageRenderer: The parent object that triggered this hook
	 * @return	void
	 */
	protected function addJS($parameters, &$pageRenderer) {

		$formprotection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get();

		if (count($parameters['jsFiles'])) {

			if (method_exists($GLOBALS['SOBE']->doc, 'issueCommand')) {
				/** @var \TYPO3\CMS\Backend\Clipboard\Clipboard $clipObj  */
				$clipObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Backend\Clipboard\Clipboard');		// Start clipboard
				$clipObj->initializeClipboard();

				$clipBoardHasContent = FALSE;

				if(isset($clipObj->clipData['normal']['el']) && strpos(key($clipObj->clipData['normal']['el']), 'tt_content') !== FALSE) {
					$pasteURL = str_replace('&amp;', '&', $clipObj->pasteUrl('tt_content', 'DD_PASTE_UID', 0));
					if (isset($clipObj->clipData['normal']['mode'])) {
						$clipBoardHasContent = 'copy';
					} else {
						$clipBoardHasContent = 'move';
					}
				}

				$moveParams = '&cmd[tt_content][DD_DRAG_UID][move]=DD_DROP_UID';
				$moveURL = str_replace('&amp;', '&', htmlspecialchars($GLOBALS['SOBE']->doc->issueCommand($moveParams, 1)));
				$copyParams = '&cmd[tt_content][DD_DRAG_UID][copy]=DD_DROP_UID&DDcopy=1';
				$copyURL = str_replace('&amp;', '&', htmlspecialchars($GLOBALS['SOBE']->doc->issueCommand($copyParams, 1)));

				// add JavaScript library
				$pageRenderer->addJsFile(
					$GLOBALS['BACK_PATH'] . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('gridelements') . 'Resources/Public/Backend/JavaScript/dbNewContentElWizardFixDTM.js',
					$type = 'text/javascript',
					$compress = TRUE,
					$forceOnTop = FALSE,
					$allWrap = ''
				);

				// add JavaScript library
				$pageRenderer->addJsFile(
					$GLOBALS['BACK_PATH'] . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('gridelements') . 'Resources/Public/Backend/JavaScript/GridElementsDD.js',
					$type = 'text/javascript',
					$compress = TRUE,
					$forceOnTop = FALSE,
					$allWrap = ''
				);

				// add JavaScript library
				$pageRenderer->addJsFile(
					$GLOBALS['BACK_PATH'] . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('gridelements') . 'Resources/Public/Backend/JavaScript/GridElementsListView.js',
					$type = 'text/javascript',
					$compress = TRUE,
					$forceOnTop = FALSE,
					$allWrap = ''
				);

				if (!$pageRenderer->getCharSet()) {
					$pageRenderer->setCharSet($GLOBALS['LANG']->charSet ? $GLOBALS['LANG']->charSet : 'utf-8');
				}

				if(is_array($clipObj->clipData['normal']['el'])) {
				    $arrCBKeys = array_keys($clipObj->clipData['normal']['el']);
				    $intFirstCBEl = str_replace('tt_content|', '', $arrCBKeys[0]);
				}

				// pull locallang_db.xml to JS side - only the tx_gridelements_js-prefixed keys
				$pageRenderer->addInlineLanguageLabelFile('EXT:gridelements/Resources/Private/Language/locallang_db.xml', 'tx_gridelements_js');

				$pRaddExtOnReadyCode = '
					TYPO3.l10n = {
						localize: function(langKey){
							return TYPO3.lang[langKey];
						}
					}
				';

				$allowedCTypesClassesByColPos = array();
				$layoutSetup = \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction('TYPO3\\CMS\\Backend\\View\\BackendLayoutView->getSelectedBackendLayout', intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id')), $this);
				if (is_array($layoutSetup) && !empty($layoutSetup['__config']['backend_layout.']['rows.'])) {
					foreach($layoutSetup['__config']['backend_layout.']['rows.'] as $rows){
						foreach($rows as $row){
							if(!empty($layoutSetup['__config']['backend_layout.']['rows.'])) {
								foreach($row as $col){
									$classes = '';
									if($col['allowed']){
										$allowed = explode(',', $col['allowed']);
										foreach($allowed as $ctype){
											$ctype = trim($ctype);
											if($ctype === '*') {
												$classes = 't3-allow-all';
												break;
											} else {
												$classes .= 't3-allow-' . $ctype . ' ';
											}
										}
									} else {
										$classes = 't3-allow-all';
									}
									$allowedCTypesClassesByColPos[] = $col['colPos'] . ':' . trim($classes);
								}
							}
						}
					}
				}

				// add Ext.onReady() code from file
				$pageRenderer->addExtOnReadyCode(
					// add some more JS here
					$pRaddExtOnReadyCode . "
						top.pageColumnsAllowedCTypes = '" . join('|', $allowedCTypesClassesByColPos) . "';
						top.pasteURL = '" . $pasteURL . "';
						top.moveURL = '" . $moveURL . "';
						top.copyURL = '" . $copyURL . "';
						top.pasteTpl = '" . str_replace('&redirect=1', '', str_replace('DDcopy=1', 'DDcopy=1&reference=DD_REFYN', $copyURL)) . "';
						top.DDtceActionToken = '" . $formprotection->generateToken('tceAction') . "';
						top.DDtoken = '" . $formprotection->generateToken('editRecord') . "';
						top.DDpid = '" . (int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id') . "';
						top.DDclipboardfilled = '" . ($clipBoardHasContent ? $clipBoardHasContent : 'false') . "';
						top.DDclipboardElId = '" . $intFirstCBEl . "';
					" .
					// replace placeholder for detail info on draggables
					str_replace(
						array(
							'top.skipDraggableDetails = 0;',
							// set extension path
							'insert_ext_baseurl_here',
							// set current server time
							'insert_server_time_here',
							// additional sprites
							'top.geSprites = {};',
							// back path
							"top.backPath = '';"
						),
						array(
							$GLOBALS['BE_USER']->uc['dragAndDropHideNewElementWizardInfoOverlay'] ? 'top.skipDraggableDetails = true;' : 'top.skipDraggableDetails = false;',
							// set extension path
							\TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl('/' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('gridelements')),
							// set current server time, format matches "+new Date" in JS, accuracy in seconds is fine
							time() . '000',
							// add sprite icon classes
							"top.geSprites = {
								copyfrompage: '" . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconClasses('extensions-gridelements-copyfrompage') . "',
								pastecopy: '" . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconClasses('extensions-gridelements-pastecopy') . "',
								pasteref: '" . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconClasses('extensions-gridelements-pasteref') . "'
							};",
							"top.backPath = '" . $GLOBALS['BACK_PATH'] . "';"
						),
						// load content from file
						file_get_contents(
							\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('gridelements') . 'Resources/Public/Backend/JavaScript/GridElementsDD_onReady.js'
						)
					),
					TRUE
				);
			}
		}
	}

	/**
	 * method that adds CSS files within the page renderer
	 *
	 * @param	array	            $parameters: An array of available parameters while adding CSS to the page renderer
	 * @param	\TYPO3\CMS\Core\Page\PageRenderer $pageRenderer: The parent object that triggered this hook
	 * @return	void
	 */
	protected function addCSS($parameters, &$pageRenderer) {
		if (count($parameters['cssFiles'])) {
			// get configuration
			$this->confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['gridelements']);
			$filename = $this->confArr['additionalStylesheet'];
			if($filename){
				// evaluate filename
				if (substr($filename, 0, 4) === 'EXT:') { // extension
					list($extKey, $local) = explode('/', substr($filename, 4), 2);
					$filename = '';
					if (strcmp($extKey, '') && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extKey) && strcmp($local, '')) {
						$filename = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($extKey) . $local;
					}
				}
				$pageRenderer->addCssFile($filename, 'stylesheet', 'screen');
			}
		}
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/Classes/Hooks/PageRenderer.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/Classes/Hooks/PageRenderer.php']);
}

