# Module **Admin** for EnjoysCSM

add after require into root composer.json
```yaml
"scripts": {
  "post-install-cmd": [
      "cd ./WYSIWYG/summernote && yarn install",
      "cd ./modules/admin && yarn install"
  ],
}
```

