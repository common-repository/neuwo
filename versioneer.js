const fs = require('fs')

const FILE_PATH = 'neuwo.php'
const VERSION_PREFIX = '1.3.1'
const ZIPD = 'neuwo.zip'

if (fs.existsSync(FILE_PATH)) console.log('file found..')
else process.exit(1)

let content = fs.readFileSync(FILE_PATH, 'utf8')

let zero = (i) => (i < 10 ? '0' + i : i);

function version_now() {
  const now = new Date()
  const DATE_SUFFIX = [zero(now.getMonth() + 1), zero(now.getDate()), zero(now.getHours()), zero(now.getMinutes())].join('')
  return [VERSION_PREFIX,  DATE_SUFFIX].join('.')
}

let old_ver = content.match(/Version: (.*)/)
if (old_ver && old_ver[1]) {
  let version_zip = (prefix, version_number) => prefix + '-' + version_number.trim().replace(/\./g, '-') + '.zip';
  if (fs.existsSync(ZIPD)) {
    fs.copyFileSync(ZIPD, version_zip(ZIPD.slice(0, ZIPD.indexOf('.')), old_ver[1]));
  }
}


let v = version_now()

content = content.replace(/Version: .*/, 'Version: ' + v)
let do_update = true;

if (do_update) {
  fs.writeFileSync(FILE_PATH, content)
  console.log('updated version to', v)
}
