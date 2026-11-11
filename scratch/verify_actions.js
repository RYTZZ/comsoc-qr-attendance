const puppeteer = require('puppeteer');

(async () => {
    const browser = await puppeteer.launch({
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    const page = await browser.newPage();
    await page.setViewport({ width: 1280, height: 900 });

    // Login as Super Admin / User
    await page.goto('http://localhost:8000/login', { waitUntil: 'networkidle2' });
    await page.type('input[name="login"]', 'grietarytzz9009@gmail.com');
    await page.type('input[name="password"]', 'password');
    await Promise.all([
        page.click('button[type="submit"]'),
        page.waitForNavigation({ waitUntil: 'networkidle2' })
    ]);

    console.log('Logged in, current URL:', page.url());

    // Navigate to announcements
    await page.goto('http://localhost:8000/announcements', { waitUntil: 'networkidle2' });
    console.log('Announcements page loaded');

    // Wait for discussion section
    await page.waitForSelector('.announcement-engagement-root, [x-data*="announcementEngagement"]', { timeout: 10000 });

    // Scroll down to discussion
    await page.evaluate(() => {
        window.scrollBy(0, 800);
    });
    await new Promise(r => setTimeout(r, 1500));

    // Capture screenshot of comment action buttons
    await page.screenshot({ path: 'public/discussion_actions_check.png', fullPage: false });
    console.log('Saved public/discussion_actions_check.png');

    // Try clicking the three-dot button on the first comment
    const threeDotButton = await page.$('button[aria-label="Comment actions"]');
    if (threeDotButton) {
        console.log('Found three-dot action button! Clicking it...');
        await threeDotButton.click();
        await new Promise(r => setTimeout(r, 600));
        await page.screenshot({ path: 'public/discussion_actions_menu_open.png', fullPage: false });
        console.log('Saved public/discussion_actions_menu_open.png');
    } else {
        console.log('Could not find button[aria-label="Comment actions"]');
    }

    await browser.close();
})();

