# WP Service Portfolio Manager - WordPress Plugin

![WordPress](https://img.shields.io/badge/WordPress-%23117AC9.svg?style=for-the-badge&logo=WordPress&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-%23777BB4.svg?style=for-the-badge&logo=php&logoColor=white)
![Gutenberg](https://img.shields.io/badge/Gutenberg-Editor-%233499DB.svg?style=for-the-badge)
![License](https://img.shields.io/badge/License-GPLv3-blue.svg?style=for-the-badge)

A powerful, extensible WordPress plugin for creating and managing service portfolios with full Gutenberg block support and complete internationalization.

## 🚀 Features

- **Custom Post Type**: Dedicated 'Services' post type with full WordPress integration
- **Extensible Taxonomies**: Add unlimited custom taxonomies to organize your services
- **Gutenberg Blocks**: Modern block editor integration with live preview
- **Shortcode Support**: Backward compatible shortcode for traditional usage
- **Fully Internationalized**: Ready for translation with proper text domain
- **Responsive Design**: Mobile-first CSS grid layouts
- **Developer Friendly**: Extensive hooks, filters, and extensibility points

## 📦 Installation

1. Download the plugin files and upload to `/wp-content/plugins/wp-service-portfolio-manager/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Start creating services in the new 'Services' admin menu

## 🎯 Quick Start

### Using Gutenberg Blocks

1. Edit any post or page with Gutenberg editor
2. Add the "Services Grid" or "Single Service" block
3. Configure settings in the block sidebar
4. Publish and view your services!

### Using Shortcode

```php
[display_services count="6" show_excerpt="true" columns="3"]
```

## 🔧 Configuration

### Basic Shortcode Parameters

| Parameter | Description | Default |
|-----------|-------------|---------|
| `count` | Number of services to display | 4 |
| `columns` | Number of grid columns | 3 |
| `category` | Filter by category slug | '' |
| `show_excerpt` | Display service excerpt | false |
| `show_date` | Display publish date | false |
| `image_size` | Featured image size | 'medium' |
| `orderby` | Sort order (date, title, rand) | 'date' |
| `order` | Sort direction (ASC/DESC) | 'DESC' |

### Available Gutenberg Blocks

- **Services Grid**: Display services in a customizable grid layout
- **Single Service**: Show individual service with various display options

## 🛠 Extending the Plugin

### Adding Custom Taxonomies

```php
add_action( 'digima_services_loaded', function( $plugin ) {
    $plugin->add_taxonomy( 'service_tags', array(
        'singular' => __( 'Tag', 'wp-service-portfolio-manager' ),
        'plural'   => __( 'Tags', 'wp-service-portfolio-manager' ),
        'slug'     => 'service-tags',
        'hierarchical' => false,
    ) );
} );
```

### Customizing Post Type Support

```php
add_filter( 'digima_services_supports', function( $supports ) {
    $supports[] = 'custom-fields';
    $supports[] = 'comments';
    return $supports;
} );
```

### Available Hooks

| Hook | Description |
|------|-------------|
| `digima_services_loaded` | Fires when plugin is initialized |
| `digima_services_post_type_args` | Filter post type registration args |
| `digima_services_taxonomy_args` | Filter taxonomy registration args |
| `digima_services_shortcode_defaults` | Filter shortcode default attributes |
| `digima_services_query_args` | Filter services query arguments |
| `digima_service_after_content` | Action after service content display |
| `digima_service_item_class` | Filter service item CSS class |

## 🌐 Internationalization

The plugin is fully translation-ready with the text domain `wp-service-portfolio-manager`. Translation files should be placed in `/languages/` directory.

### Making Strings Translatable

All user-facing strings use WordPress translation functions:

```php
__( 'Services', 'wp-service-portfolio-manager' );
_e( 'Add New Service', 'wp-service-portfolio-manager' );
```

## 💻 Developer Usage

### Programmatic Service Retrieval

```php
$services_plugin = Digima_Services_Plugin::get_instance();
$taxonomies = $services_plugin->get_taxonomies();

// Query services
$services = get_posts( array(
    'post_type' => 'dg_cpt_service',
    'tax_query' => array(
        array(
            'taxonomy' => 'dg_services_categories',
            'field'    => 'slug',
            'terms'    => 'premium',
        ),
    ),
) );
```

### Custom Template Overrides

Create these files in your theme to override plugin templates:

- `wp-service-portfolio-manager/grid-container.php`
- `wp-service-portfolio-manager/service-item.php`
- `wp-service-portfolio-manager/single-service.php`

## 🎨 Styling

The plugin includes basic CSS for the grid layout. You can override styles in your theme:

```css
.digima-services-grid {
    /* Your custom styles */
}

.digima-service-item {
    /* Your custom styles */
}

.digima-services-pagination {
    /* Your custom styles */
}
```

## 🔄 Changelog

### Version 2.0.0
- Complete rewrite with object-oriented architecture
- Gutenberg blocks integration
- Enhanced extensibility with hooks and filters
- Improved internationalization support
- Better code organization and documentation
- Renamed to WP Service Portfolio Manager

### Version 1.0.0
- Initial release with basic services post type
- Shortcode functionality
- Basic taxonomy support

## 🤝 Contributing

We welcome contributions! Please feel free to submit pull requests, report bugs, or suggest new features.

### Development Setup

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/new-feature`
3. Commit your changes: `git commit -am 'Add new feature'`
4. Push to the branch: `git push origin feature/new-feature`
5. Submit a pull request

## 📜 License

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.

## 💼 Custom Development

Need custom WordPress development services or modifications to this plugin?

**Hire me on Upwork:** [Your Upwork Profile Link]

I specialize in:
- Custom WordPress plugin development
- Gutenberg block creation
- Plugin customization and extension
- WordPress theme development
- API integrations

## 🆘 Support

- **Documentation**: [GitHub Wiki](#)
- **Issues**: [GitHub Issues](#)
- **Author**: Digima
- **Custom Development**: [Upwork Profile](Your Upwork Profile Link)

## 🙏 Credits

Built with ❤️ for the WordPress community. Special thanks to all contributors and testers.

---

**⭐ If you find this plugin useful, please consider giving it a star on GitHub!**

---

*Copyright (c) 2024 Digima. Licensed under the GPL v3 license.*
