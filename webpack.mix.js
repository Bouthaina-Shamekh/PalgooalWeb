const mix = require('laravel-mix');

mix
  .setPublicPath('public')
  .postCss('resources/css/tailwind.css', 'public/assets/tamplate/css/app.css')
  .options({ processCssUrls: false })
  .version();