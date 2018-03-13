const path = require('path')
const {existsSync, statSync} = require('fs')
const {spawn} = require('child_process')
const extend = require('extend')
const {validatorTypes} = require('./fixtures')

const getSymbolType = str => {
  const errTypes = {
    'tc-required': 'error',
    'tc-warning': 'warning',
    'tc-info': 'info',
    'tc-recommended': 'info'
  }

  for (const id in errTypes) {
    if (Object.prototype.hasOwnProperty.call(errTypes, id) && str.indexOf(id) > 0) {
      return errTypes[id]
    }
  }
}

module.exports = (dir, options = {}) => new Promise((resolve, reject) => {
  if (!existsSync(dir) && !statSync(dir).isDirectory()) {
    reject(new Error('Invalid Path'))
  }

  options = extend({
    excludes: [],
    validator: [
      validatorTypes.THEME_MENTOR,
      validatorTypes.THEME_CHECK
    ],
    prettify: false,
    warning: true
  }, options)

  const data = []
  const args = JSON.stringify({
    path: dir,
    excludes: options.excludes,
    validator: options.validator
  })

  const rootDir = path.resolve(__dirname, '..')
  const scriptPath = path.join(rootDir, 'php-scripts', 'validators', 'validator.php')
  const validatorScript = spawn('php', [scriptPath, args])

  validatorScript.stdout.on('data', buffer => {
    data.push(buffer.toString())
  })

  const errData = []
  validatorScript.stderr.on('data', buffer => {
    errData.push(buffer.toString())
  })

  validatorScript.stderr.on('close', () => {
    if (errData.length > 0) {
      reject(new Error(errData.join('')))
    }
  })

  validatorScript.on('exit', code => {
    if (code !== 0) {
      reject(new Error('Non-Zero Exit.'))
    }

    try {
      const results = JSON.parse(data.join('')).map(validator => {
        if (validator.type === validatorTypes.THEME_CHECK) {
          validator.result = validator.result.map(resultItem => {
            resultItem.items = resultItem.items.map(item => {
              const type = getSymbolType(item)
              let message = item.split(':')
              message.shift()

              message = message.join('')

              return {
                type,
                message
              }
            })

            return resultItem
          })
        }

        return validator
      })

      resolve(results)
    } catch (err) {
      throw new Error(`Error: ${err.message} with data ${data}`)
    }
  })
})
