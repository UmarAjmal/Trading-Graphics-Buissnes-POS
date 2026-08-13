import { chromium } from 'playwright'

const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:8000'
const OUTPUT_DIR = process.env.OUTPUT_DIR || 'ui-snapshots'

const pages = [
  { path: '/', name: 'home' },
  { path: '/dashboard', name: 'dashboard' },
  { path: '/pos', name: 'pos' },
  { path: '/inventory', name: 'inventory' },
  { path: '/purchases', name: 'purchases' },
  { path: '/sales', name: 'sales' },
  { path: '/reports/customers', name: 'reports-customers' },
  { path: '/reports/suppliers', name: 'reports-suppliers' }
]

const ensureDir = async (fsPromises, dir) => {
  try {
    await fsPromises.mkdir(dir, { recursive: true })
  } catch (err) {
    if (err.code !== 'EEXIST') throw err
  }
}

const capture = async (page, theme, name) => {
  await page.addInitScript(themeScript(theme))
  await page.goto(BASE_URL + name.path, { waitUntil: 'networkidle' })
  await page.setViewportSize({ width: 1440, height: 900 })
  await page.waitForTimeout(500)
  await page.screenshot({ path: `${OUTPUT_DIR}/${name.name}-${theme}.png`, fullPage: true })
}

const themeScript = (theme) => {
  return `
    (() => {
      const root = document.documentElement;
      const storageKey = 'theme';
      root.classList.remove('light', 'dark');
      root.classList.add('${theme}');
      try { localStorage.setItem(storageKey, '${theme}'); } catch (e) {}
    })();
  `
}

const run = async () => {
  const { promises: fsPromises } = await import('fs')
  await ensureDir(fsPromises, OUTPUT_DIR)

  const browser = await chromium.launch({ headless: true })
  const context = await browser.newContext({ viewport: { width: 1440, height: 900 } })
  const page = await context.newPage()

  for (const entry of pages) {
    console.log(`Capturing ${entry.name}...`)
    await capture(page, 'light', entry)
    await capture(page, 'dark', entry)
  }

  await browser.close()
  console.log(`Snapshots saved to ${OUTPUT_DIR}/`)
}

run().catch((err) => {
  console.error('Snapshot run failed:', err)
  process.exit(1)
})
