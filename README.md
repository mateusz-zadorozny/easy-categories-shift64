# Easy Categories Shift64

A powerful WordPress plugin for WooCommerce that enables drag-and-drop reordering of product categories with hierarchy management.

## Description

Easy Categories Shift64 provides an intuitive interface for managing WooCommerce product category order. Instead of manually editing each category's order value, you can simply drag and drop categories or use arrow buttons to reorder them.

### Features

- **Drag & Drop Reordering** - Grab categories by the handle and move them to a new position
- **Arrow Navigation** - Use ▲▼ buttons to move categories up/down within the same level
- **Hierarchy Management** - Use ◀▶ buttons to change category depth (promote/demote)
- **Parent Categories Filter** - Toggle to show only root-level categories for faster editing
- **Childless Highlighting** - Option to visually highlight root categories without subcategories
- **Auto-save** - Changes are saved automatically after each action
- **REST API** - Built on WordPress REST API for reliable data handling

## Requirements

- WordPress 6.8+
- WooCommerce 10.0+
- PHP 8.0+

## Installation

1. Upload the `easy-categories-shift64` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **Products → Category Order** in the admin menu

## Usage

### Basic Reordering

1. Go to **Products → Category Order**
2. Drag categories using the ☰ handle to reorder
3. Or use the arrow buttons:
   - **▲** Move up within current level
   - **▼** Move down within current level
   - **◀** Promote to parent level (or make root category)
   - **▶** Demote as child of previous sibling

### Filters

- **Show only parent categories** - Displays only root-level categories. Drag & drop is disabled in this mode; use ▲▼ arrows only.
- **Highlight root categories without subcategories** - Adds yellow background to root categories that have no children.

## Screenshots

The plugin adds a new submenu under WooCommerce Products with a clean, WordPress-native interface for category management.

## REST API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/wp-json/ecs64/v1/update-order` | POST | Update category order/hierarchy |
| `/wp-json/ecs64/v1/get-categories` | GET | Get category tree |

## Changelog

### 0.0.1
- Initial release
- Drag & drop category reordering
- Arrow buttons for up/down/left/right movement
- Parent categories filter
- Childless root categories highlighting
- REST API integration

## Author

**SHIFT64**  
[https://shift64.com](https://shift64.com)

Developed by Mateusz Zadorożny

## License

GPL v2 or later
