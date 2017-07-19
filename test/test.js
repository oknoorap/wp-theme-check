import path from 'path'
import test from 'ava'
import themeCheck from '../src/theme-check'

const themeDir = path.join(__dirname, 'theme-test')
const check = () => themeCheck(themeDir)

test('init should be success.', async t => {
  await check().then(() => {
    t.pass('initialized')
  })
})

test('wrong path throws an error.', async t => {
  await t.throws(themeCheck(path.join(__dirname, 'invalid-folder')))
})
