<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Jérôme Schneider <mail@jeromeschneider.fr>
*  All rights reserved
*
*  http://baikal.codr.fr
*
*  This script is part of the Baïkal Server project. The Baïkal
*  Server project is free software; you can redistribute it
*  and/or modify it under the terms of the GNU General Public
*  License as published by the Free Software Foundation; either
*  version 2 of the License, or (at your option) any later version.
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

ini_set("display_errors", 1);
error_reporting(E_ALL);
define("PROJECT_CONTEXT_BASEURI", "/admin/");

define("BAIKAL_CONTEXT", TRUE);
define("BAIKAL_CONTEXT_ADMIN", TRUE);

# Bootstraping Flake
require_once(dirname(dirname(dirname(__FILE__))) . "/Flake/Framework.php");	# ../../
\Flake\Framework::bootstrap();

# Bootstrap BaikalAdmin
\BaikalAdmin\Framework::bootstrap();

# Assert that BaikalAdmin is enabled
\BaikalAdmin\Core\Auth::assertEnabled();

# Create and setup a page object
$oPage = new \Flake\Controller\Page(BAIKALADMIN_PATH_TEMPLATES . "Page/index.html");
$oPage->injectHTTPHeaders();
$oPage->setTitle("Baïkal Web Admin");
$oPage->setBaseUrl(PROJECT_URI);

# Authentication
if(
	\BaikalAdmin\Core\Auth::isAuthenticated() === FALSE &&
	\BaikalAdmin\Core\Auth::authenticate() === FALSE
) {
	$oPage->zone("navbar")->addBlock(new \BaikalAdmin\Controller\Navigation\Topbar\Anonymous());
	$oPage->zone("Payload")->addBlock(new \BaikalAdmin\Controller\Login());
} else {
	$oPage->zone("navbar")->addBlock(new \BaikalAdmin\Controller\Navigation\Topbar());

	# Route the request
	$GLOBALS["ROUTER"]::route($oPage);
}

# Render the page
echo $oPage->render();