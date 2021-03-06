<?php

/**
 * This file is part of the Manuscript Desk (github.com/akvankorlaar/manuscriptdesk)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * @package MediaWiki
 * @subpackage Extensions
 * @author Arent van Korlaar <akvankorlaar 'at' gmail 'dot' com> 
 * @copyright 2015 Arent van Korlaar
 */
/**
 * Usage: Add the following line in LocalSettings.php:
 * require_once( "$IP/extensions/StylometricAnalysis/StylometricAnalysis.php" );
 */
// Check environment
if (!defined('MEDIAWIKI')) {
    echo( "This is an extension to the MediaWiki package and cannot be run standalone.\n" );
    die(-1);
}

/* Configuration */

//Credits
$wgExtensionCredits['parserhook'][] = array(
  'path' => __FILE__,
  'name' => 'stylometricAnalysis',
  'author' => 'Arent van Korlaar',
  'version' => '1.0',
  'url' => 'https://manuscriptdesk.uantwerpen.be',
  'description' => 'This extension permits users to perform Stylometric Analysis on texts for the Manuscript Desk.',
  'descriptionmsg' => 'stylometricanalysis-desc'
);

//Shortcut to this extension directory
$dir = __DIR__ . '/';

//Auto load classes 
$wgAutoloadClasses['StylometricAnalysisHooks'] = $dir . '/StylometricAnalysis.hooks.php';
$wgExtensionMessagesFiles['StylometricAnalysis'] = $dir . '/StylometricAnalysis.i18n.php';
$wgAutoloadClasses['StylometricAnalysisViewer'] = $dir . '/specials/StylometricAnalysisViewer.php';
$wgAutoloadClasses['StylometricAnalysisRequestProcessor'] = $dir . '/specials/StylometricAnalysisRequestProcessor.php';
$wgAutoloadClasses['StylometricAnalysisWrapper'] = $dir . '/specials/StylometricAnalysisWrapper.php';

////Register auto load for the special page classes and register special pages
$wgAutoloadClasses['SpecialStylometricAnalysis'] = $dir . '/specials/SpecialStylometricAnalysis.php';

$wgSpecialPages['StylometricAnalysis'] = 'SpecialStylometricAnalysis';

//Extra file loaded later 
$wgResourceModules['ext.stylometricanalysiscss'] = array(
  'localBasePath' => dirname(__FILE__) . '/css',
  'styles' => '/ext.stylometricanalysiscss.css',
);

$wgResourceModules['ext.stylometricanalysisbuttoncontroller'] = array(
  'localBasePath' => dirname(__FILE__) . '/js',
  'scripts' => '/ext.stylometricanalysisbuttoncontroller.js',
  'messages' => array(
    'stylometricanalysis-error-manycollections',
  ),
);

$wgResourceModules['ext.stylometricanalysissvg'] = array(
  'localBasePath' => dirname(__FILE__) . '/js',
  'scripts' => array('/svg-pan-zoom.min.js', '/ext.stylometricanalysissvg.js'),
);

//Instantiate the stylometricAnalysisHooks class and register the hooks
$stylometricanalysis_hooks = ObjectRegistry::getInstance()->getStylometricAnalysisHooks();

$wgHooks['MediaWikiPerformAction'][] = array($stylometricanalysis_hooks, 'onMediaWikiPerformAction');
$wgHooks['AbortMove'][] = array($stylometricanalysis_hooks, 'onAbortMove');
$wgHooks['ArticleDelete'][] = array($stylometricanalysis_hooks, 'onArticleDelete');
$wgHooks['PageContentSave'][] = array($stylometricanalysis_hooks, 'onPageContentSave');
$wgHooks['BeforePageDisplay'][] = array($stylometricanalysis_hooks, 'onBeforePageDisplay');
$wgHooks['ResourceLoaderGetConfigVars'][] = array($stylometricanalysis_hooks, 'onResourceLoaderGetConfigVars');
$wgHooks['UnitTestsList'][] = array($stylometricanalysis_hooks, 'onUnitTestsList');
$wgHooks['OutputPageParserOutput'][] = array($stylometricanalysis_hooks, 'onOutputPageParserOutput');
