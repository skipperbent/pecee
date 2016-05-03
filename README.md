# [WIP] pecee php framework

**Fast, lightweight, open source MVC PHP framework based on Microsoft MVVM.**

***THIS DOCUMENTATION IS UNDER CONSTRUCTION.***

#### What it is and was its not

Pecee is a flexible framework that helps developers by working WITH and not AGAINST the developer, by not limiting the possibilities or ideas the developer might have. Other framework has a tendency to be easy-to-setup and implement; but limiting the developers by only allowing to programming in certains ways of within the boundaries the Framework has defined, which often led developeres to find ways to around the system, to "hack" it in order to get it working for a specific usecase.

**Dont write the same logic twice:**
The framework is inspired by many other frameworks in both syntax and design. We like simple symtax that Laravel provides; but hates the slowness - facades and autoloaded classes that comes with it. We like Microsoft's way of handeling user-controls; having once piece of code (for instance a login-box) and repeating it multiple places - in different shapes and styles.

Pecee is not a framework that gives you everything out of the box. For example; though there is functionality to handle users-logins, resets etc - you still have to manually implement it in your site. Also; we're a big believer in composer - so instead of inventing the deep plate twice; use the dependencies you like for templating; file-management etc.

# Overview

- Test
 - Test
- Test
 - Test

## Demo project

Demo project available here: https://github.com/skipperbent/pecee-project

## Features:

- Fully customisable template engine. Overwrite `render()` method to add your own templating engine through composer or create your own.

- Every template has class, called a `Widget`, behind which renders before the view. From here you can set properties, create methods and you can even render widgets inside a widget - this makes it super easy the create small pieces of functionality, that can be reused wherever you like.

- Because every template is basically a class, you can extend functionality from other widgets and reuse the same functionality or overwrite the things you want to change.

- Don't like something? Everything is 100% object oriented, so every little piece of code can be easily extended.

## Installation

```
cd /path/to/project
git clone https://sessingo@bitbucket.org/sessingo/pecee-project.git
composer install
```

Point your webserver to ```/path/to/project/app/public```

Make sure url-rewriting is enabled on your server and pointing to the ```index.php``` file.

We recommend that you look at the demo project for futher installation instructions:
https://github.com/skipperbent/pecee-project

## The MIT License (MIT)

Copyright (c) 2016 Simon Sessingø / pecee

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

