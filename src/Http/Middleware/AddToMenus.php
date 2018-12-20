<?php
namespace ProcessMaker\Package\PackageSkeleton\Http\Middleware;

use Closure;
use Lavary\Menu\Facade as Menu;


class AddToMenus 
{

    public function handle($request, Closure $next)
    {

        // Add a menu option to the top to point to our page
        
        $menu = Menu::get('sidebar_admin')->get(__('menus.sidebar_admin.organization'));

        // Add our menu item to the top nav
        $menu->add('', [
            'route' => 'package.skeleton.index',
            'icon' => 'fa-puzzle-piece',
        ]);
        return $next($request);
    }
    
}