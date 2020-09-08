# Erebus site template
This is a template for a WP site.

## Example blocks and Field Groups
Within this theme you will find in the ```classes/ACF``` folder - Blocks and FieldGroups directories.
These are here for example only on how to register a new block for Gutenberg along with the field groups for the block.

### Seeing the example blocks in action
To activate the example blocks follow these steps:

1. Update the namespaces in both testimonials classes to match your site namespace
```
Change this:
namespace NanoSoup\Erebus\ACF\Blocks;

To this:
namespace {{ SITE_NAMESPACE }}\ACF\Blocks;
```
2. Add the following code to your functions.php file:
```
$fieldGroups = new \{{ SITE_NAMESPACE }}\ACF\FieldGroups\Testimonials();
$fieldGroups->init();

$testimonials = new \{{ SITE_NAMESPACE }}\ACF\Blocks\Testimonials();
$testimonials->init();
```
You will then be able to select and use the Testimonials block within the Gutenberg editor