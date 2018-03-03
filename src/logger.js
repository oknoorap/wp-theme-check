/* eslint-disable no-console */
const chalk = require('chalk')
const cheerio = require('cheerio')
const logSymbols = require('log-symbols')
const {AllHtmlEntities} = require('html-entities')

const entities = new AllHtmlEntities()

exports.blank = () => {
  console.log('')
}

const tab = (msg, count = 1) => {
  const tabs = []

  for (let i = 0; i < count; i++) {
    tabs.push('    ')
  }

  return `${tabs.join('')}${msg}`
}

const stdlog = (data, symbol = 'error') => {
  const isObject = typeof data === 'object'
  const message = isObject ? `${data.message}` : data

  console.error(tab(`${logSymbols[symbol]} ${message}`))

  if (isObject && data.snippet) {
    console.info(tab(`${logSymbols.info} ${data.snippet}`))
  }
}

exports.tab = tab

exports.underline = msg => {
  console.log(tab(`${chalk.underline.white(msg)}`))
}

exports.title = msg => {
  console.info(tab(chalk.bgBlue(`-------- ${msg} --------`)))
}

exports.info = msg => {
  console.info(tab(`${logSymbols.info} ${chalk.cyan(msg)}`))
}

exports.error = data => stdlog(data)
exports.warning = data => stdlog(data, 'warning')

// Error log for theme-check
exports.error2 = data => {
  const $ = cheerio.load(data.message)
  $('strong').each((i, el) => {
    $(el).replaceWith(() => chalk.magenta(entities.decode($(el).text())))
  })

  $('a').each((i, el) => {
    $(el).replaceWith(() => chalk.underline.blue($(el).attr('href')))
  })

  $('pre').each((i, el) => {
    $(el).replaceWith(() => {
      const text = entities.decode($(el).text())
      return '\n' + tab(chalk.underline.gray(text))
    })
  })

  const message = entities.decode($('body').html())
  console.error(tab(`${logSymbols[data.type]} ${message}`))
}

exports.done = msg => {
  console.log(tab(`${logSymbols.success} ${chalk.green(msg)}`))
}

exports.json = str => {
  console.log(JSON.stringify(str))
}
