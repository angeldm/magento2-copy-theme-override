{
    "name": "{{package_name_lc}}/magento2-theme-{{module_name_lc}}",
    "description": "",
    "config": {
       "sort-packages": true
   },
   "require": {
       "php": "~7.1.3||~7.2.0",
       "magento/framework": "*",
       "magento/theme-frontend-blank": "*"
   },
   "type": "magento2-theme",
   "license": [
       "OSL-3.0",
       "AFL-3.0"
   ],
   "autoload": {
       "files": [
           "registration.php"
       ]
   }
}
