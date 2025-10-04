const mix = require('laravel-mix');

mix
  .setPublicPath('public')
  .postCss('resources/css/tailwind.css', 'public/assets/tamplate/css/app.css')
  .postCss('resources/css/dashboard.css', 'public/assets/dashboard/css/dashboard.css')
  .options({ processCssUrls: false })
  .version();