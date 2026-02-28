# Intro

## Project Inspiration

The WP Plugin Framework has a somewhat "opinionated" way of development that just takes a little translation to get on board with. Using a defined structure allows for development process automation as well as organization, both of which I have historically found lacking in the development of custom WordPress plugins. Before diving into the structure, I would like to describe a couple challenges that highlight some of the motivations for the solutions provided.

Let's first start by looking at a particular object-oriented gap. In the core WordPress library, we are supplied with the base `\WP_Post` class to interact with instantiated custom post type data. This is a really nice thing to have when working in your IDE for autocompletion and creates a standalone reference for the contained and intended funcitonality of the post you are working with. On the other hand, there is no defined class for representing a post type. On one level that makes sense, as post types are configuration and posts are not, but for the matter of consistency, I thought it would be beautiful if there were a consistent spread of object-oriented classes that could interact together to connect information.

Secondly, I would like to address the organization of code in custom WordPress plugins. Most plugins  fall in between having some semblance of structure based on the rough naming of a few files containing as much code as reasonably possible, and a production-grade setup with a layered architecture. The truth is that most developers don't need a `cli` folder and aren't going to create unit tests for their plugin, but maintaining structure and reducing the cognitive load of process recall is something I believe everybody can benefit from. The framework meets this need halfway by utilizing a feature-based architecture, where code is organized by what it *does* and not by what it *is*. Instead of placing your post type registrations and instantiable classes in the same directory, we separate out the components into their own defined locations. You might move all of your "my account" funcitonality to a module called `My_Plugin_WooCommerce_My_Account` or set up your API routes in a `My_Plugin_API` module.

Lastly, I want to touch on the automation behind boilderplate code. When you are creating a new plugin, your best friends are the [WordPress Hook References](https://developer.wordpress.org/apis/hooks/). The usage of `add_action( ... )` is ubiquitous, and often unorchestrated. Creating new plugins comes with repetitive configuration tasks such as configuring a plugin file, separating out administrative concerns, bootstrapping plugin code, registering post types, requiring PHP files, enqueueing assets, setting up meta data storage, and more, all while following WordPress coding standards. *You just want to code, I know.* One of the goals of this framework is to automate the creation of plugins by using configuration information to directly interface with native WordPress plugins and allow the developer to focus on the context and spend less time in the WordPress documentation.

Hopefully some of the inspiration for this project has been relayed and better prepared you for the descriptions of solutions that follow.

## Project Solutions

### **Modules & Autoloader**

The concept of **modules** is probably the most central to the understanding of how to use the framework, and it is deeply intertwined with the included **autoloader**. Modules separate your plugin into functional pieces grouping together related parts of the code and provide a home for action/filter calls to work directly with WordPress. Modules can either be a single file or a nested directory of related assets, classes, and even other modules.

- For example, a `Plugin` is a `Module` which is responsible for bootstrapping the application, running (de)activation hooks, storing HTML/CSS/JS, initializing admin functionality, and registering post types. 
- In another example, an `Admin_Module` is a module which belongs to a `Plugin` module. This module is responsible for registering admin screens, loading meta boxes, and ensuring contained code only runs in an administrative context.

All of the custom code that you develop will be contained in modules that you define as well as configuration files such as `Post Type` and `Meta Box` classes. Think of a module as the entry point to an aspect of your plugin that you tie in using action/filer hooks. These supported classes are automatically registed by the included autoloader for the project. All plugin files are scanned on load and placed in a hash map, which is referenced in `spl_autoload_register()` to quickly locate project files. Below is a simple example of a module calle

```
<?php
defined( 'ABSPATH' ) or exit;

use WPPF\v1_2_2\Module;

/**
 * A class that contains the functionality for an aspect of your plugin.
 */
final class MP_My_Module extends Module
{
    /**
     * Module entry point.
     */
    public static function construct(): void
    {
        add_action( 'init', [ __CLASS__, '_init' ] );
        add_action( 'rest_api_init', [ __CLASS__, '_rest_api_init' ] );
        ...
    }

    /**
     * The function attached to the 'init' action hook.
     */
    public static function _init(): void
    {
        ...
    }

    /**
     * The function attached to the 'rest_api_init' action hook.
     */
    public static function _rest_api_init(): void
    {
        ...
    }
}
```

### **Directory Structure**

In order to illustrate the entire layout of a plugin using the framework, we can examine the file structure of a typical plugin project.

```
📦 My Plugin (my-plugin)
┃
┣─ 📂 admin
┃  ┣─ 📂 includes
┃  ┃  ┣─ 📂 meta-boxes
┃  ┃  ┗─ 📂 screens
┃  ┣─ 📂 templates
┃  ┗─ 📜 my-plugin-admin.php
┃
┃─ 📂 assets
┃  ┣─ 📂 css
┃  ┗─ 📂 js
┃
┣─ 📂 includes
┃  ┣─ 📂 classes
┃  ┣─ 📂 modules
┃  ┗─ 📂 post-types
┃
┣─ 📂 vendor (wp-plugin-framework)
┃
┗─ 📜 my-plugin.php
```

### **Singleton patterns**

Singleton-like patterns are pervasive throughout the WordPress ecosystem, and I wanted dedicated functionality to addressing their usage and incorporate those patterns directly into the framework concepts. The framework does the heavy lifting in implementing singleton patterns so that the developer does not have to stay consciously aware of them, but it is beneficial to recognize them when they appear. WordPress favors *"true-ish"* singletons through globals and instantiation of `public` methods. This last piece is one of the true differentiators in the framework singletons is that constructors are defined as `protected`. For example, we see access to the following singletons appear in WordPress/WooCommerce:

- `global $wpdb`
- `wp_roles()`
- `WooCommerce::instance()`
- `WC()->cart`

This idea is extended in the framework with the criteria for a singleton being "if there should be only one, it is a singleton". For example, there is only one `Post Type` of any particular kind, but you could make multiple `Post` object from that type. Therefore `Post Type` would be a singleton, but `Post` would *not*. There is only ever one instance of your `Plugin` available, therefore all plugin classes are singletons. In fact, ***all*** modules are singletons since they manage a subset of project code/assets. Consequently, this makes it feasible to see which plugin features have been initialized through the `global wppf_frameworks()` function class, which shows all loaded framework versions along with their active singletons. This project has chosen to utilize an `instance()` method in order to give access to the instantiated class object, which should be familiar to those working in WooCommerce. For example, we can get the plugin ID by accessing the plugin instance and calling the `get_plugin_id()` method on it.

```
My_Plugin::instance()->get_plugin_id();
```

### **Versioned namespaces**

It will probably become quickly apparent that framework classes are namespaced and that those namespaces are versioned. You will see FCQNs that look similar to `WPPF\v1_2_2\Plugin`. The reason for this is to support the usage of multiple projects running different versions of the framework at one time. The need for this should be fairly apparent, if you have multiple plugins using the framework on your site, this ensures they do not interfere with each otther.

One other notable issue encountered when versioning namespaces comes from within different Composer dependencies in the same project requiring different versions of the framework. Composer will opt to use ***only*** the highest version available when version constraints overlap, but this will break versioned namespaces as the older version will not be found. This is not something you will likely encounter every day, as each plugin has its own framework as previously discussed. However, when configuring plugin updates, you can encounter a scenario in which your plugin requires both the framework and the [plugin update helper](https://github.com/kyle-niemiec/wppf-update-helper) using Composer, but the helper uses a different version of the framework as your plugin. For this scenario, backwards-compatibility has been enabled for specific namespace versions where newer frameworks can declare support for a range of framework versions, and if a supported namespace is requested, the autoloader can provide the newer code instead.

TLDR; There are versions in the namespace. It works! You don't have to worry!

### **CLI scaffolding**

The CLI was a pipe dream for a while (so was this documentation for that matter). The framework has proven so useful in automating my work, I just thought it would be incredible to make it even more easily accessible. The CLI performs the task of generating boiler plate code for structuring a plugin, and now you can have a fully-functional plugin with custom post types, admin screens, and meta data constructed within minutes. The CLI is used throughout the tutorial to simplify the demonstration of creating a plugin. There are more features I would like to add to the CLI over time, and I look forward to sharing those when they are completed.