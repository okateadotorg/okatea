<?php
/*
 * This file is part of Okatea.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

$collection = new RouteCollection();

$collection->add('home',
	new Route('/', array('_controller' => 'Tao\Admin\Controller\Home::homePage'))
);

$collection->add('login',
	new Route('/login', array('_controller' => 'Tao\Admin\Controller\Connexion::login'))
);
$collection->add('logout',
	new Route('/logout', array('_controller' => 'Tao\Admin\Controller\Connexion::logout'))
);
$collection->add('forget_password',
	new Route('/forget-password', array('_controller' => 'Tao\Admin\Controller\Connexion::forget_password'))
);

$collection->add('config_general',
	new Route('/configuration/general', array('_controller' => 'Tao\Admin\Controller\Config\General::page'))
);
$collection->add('config_display',
	new Route('/configuration/display', array('_controller' => 'Tao\Admin\Controller\Config\Display::page'))
);


return $collection;
