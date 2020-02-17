# Blocks to REST

Delivers all WordPress Gutenberg block data into REST in exactly the form it takes when saving a post (i.e., before any backend parsing occurs). This facilitates the dynamic rendering of all blocks on the front end.

## Usage

Clone this repository into your `wp-content/plugins` directory. Run:

```
npm install
npm run build
```

Activate the plugin. Existing posts will be unaffected, but all new and edited posts (that support meta) will now have an array of unparsed block data in the post REST response.