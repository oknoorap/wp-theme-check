# :heavy_check_mark: WordPress Theme Check
WordPress Theme Check in Node.js. [Theme Mentor](https://github.com/Ataurr/Theme-Mentor-For-Themeforest) and [Theme Check](https://wordpress.org/plugins/theme-check)

## Motivation
After 5 years developing themes in WordPress, I realize that implementing good workflow is hard. After wandering in javascript world, my perspective about workflow really changed. All tasks are automated. Today, I'm going back to WordPress world. Here is reason why this project is good in WordPress development workflow:

* You don't need to install WordPress and setup server in CI server.
* You don't need to install Plugin to check is theme valid or not.
* Your wasting time to check in plugin page will be handled in Continous Integration script.

## :computer: Install 
Using NPM  
```bash
$ npm install wp-theme-check --save
```

Using Yarn
```bash
$ yarn add wp-theme-check
```

If you want to use CLI version, please install it globally.

```bash
# NPM
$ npm install wp-theme-check -g

# Yarn
# Use sudo in OSX
$ yarn global wp-theme-check 
```

## Usage
```javascript
const themeCheck = require('wp-theme-check')

themeCheck(`/path/to/wordpress/wp-content/themes/theme-name`)
  .then(logs => {
    console.log(logs)
  })
  .catch(err => {
    console.error(err.message)
  })
```

## :zap: CLI
```bash
themecheck - WordPress theme check

  USAGE

  themecheck [path]

  ARGUMENTS

  [path]      Script path could be theme directory.      optional      

  OPTIONS

  --with-theme-mentor Only use theme mentor as validator. optional      
  --with-theme-check Only use theme check as validator. optional      

  GLOBAL OPTIONS

  -h, --help         Display help                                      
  -V, --version      Display version                                   
  --no-color         Disable colors                                    
  --quiet            Quiet mode - only displays warn and error messages
  -v, --verbose      Verbose mode - will also output debug messages
```

## Related
* [WPCS in Node.js](https://github.com/oknoorap/wpcs)

## License
MIT © [oknoorap](https://github.com/oknoorap)
