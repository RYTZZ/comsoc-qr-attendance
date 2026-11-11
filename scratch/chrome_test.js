import { execSync } from 'child_process';
import http from 'http';
import fs from 'fs';

// Launch chrome with remote debugging
const chromePath = '"C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe"';
const userDataDir = 'c:\\Users\\FixAdmin\\Desktop\\comsoc-qr-attendance\\scratch\\chrome_profile';

console.log('Chrome runner starting...');

