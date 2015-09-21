# [WIP] pecee php framework

Fast, lightweight, open source MVC PHP framework based on Microsoft MVVM.

Features:

- Fully customisable template engine. Overwrite render() method to add your own templating engine or create your own using the Taglib class.

- Every template has class, called a Widget, behind it. From here you can set properties, create methods and you can even render widgets inside a widget - this makes it super easy the create small pieces of functionality, that can be reused wherever you like.

- Because every template is basiclly a class, you can extend functionality from other widgets and reuse the same functionality or overwrite the things you want to change.

- Don't like something? Everything is 100% object oriented, so every little piece of code can be easily extended. A great example is PeceeCamp which uses some of the functionality of PeceeCamp, but completely rewrites the way routing is working.

## Installation ##

```
cd /path/to/project
git clone https://sessingo@bitbucket.org/sessingo/pecee-project.git
composer install
```

Point your webserver to /path/to/project/app/www
