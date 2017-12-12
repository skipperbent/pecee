# pecee php framework

**Fast, lightweight, open source MVC PHP framework based on Microsoft MVVM.**

## Demo project

Demo project available here: https://github.com/skipperbent/pecee-project

## Features:

- Fully customisable template engine. Overwrite render() method to add your own templating engine through composer or create your own.

- Every template has class, called a Widget, behind which renders before the view. From here you can set properties, create methods and you can even render widgets inside a widget - this makes it super easy the create small pieces of functionality, that can be reused wherever you like.

- Because every template is basically a class, you can extend functionality from other widgets and reuse the same functionality or overwrite the things you want to change.

- Don't like something? Everything is 100% object oriented, so every little piece of code can be easily extended. 

## Installation

```
cd /path/to/project
git clone https://sessingo@bitbucket.org/sessingo/pecee-project.git
composer install
```

Point your webserver to ```/path/to/project/app/www```

We recommend that you look at the demo project for futher installation instructions:
https://github.com/skipperbent/pecee-project

## The MIT License (MIT)

Copyright (c) 2016 Simon Sessingø / pecee framework

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

