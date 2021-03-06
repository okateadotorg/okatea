<?php
/*
 * This file is part of Okatea.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
$view->extend('Layout');

$okt->page->addTitleTag($okt->page->getSiteTitleTag(null, $okt->page->getSiteTitle()));
$okt->page->addTitleTag(__('c_c_unauthorized'));

$okt->page->breadcrumb->add(__('c_c_unauthorized'));

?>

<h1><?php _e('c_c_unauthorized') ?></h1>

<p><?php _e('c_c_access_is_denied') ?></p>
