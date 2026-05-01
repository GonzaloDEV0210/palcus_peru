const fs = require('fs');
const path = require('path');

function processDir(dir) {
  const files = fs.readdirSync(dir);
  files.forEach(file => {
    const fullPath = path.join(dir, file);
    if (fs.lstatSync(fullPath).isDirectory()) {
      if (file !== 'node_modules' && file !== '.git') processDir(fullPath);
    } else if (file.endsWith('.html') || file.endsWith('.js')) {
      let content = fs.readFileSync(fullPath, 'utf8');
      
      // Remove version numbers like /
      const regex = /\/v\d{7,15}\//g;
      
      if (regex.test(content)) {
        content = content.replace(regex, '/');
        fs.writeFileSync(fullPath, content);
        console.log(`Version removed: ${fullPath}`);
      }
    }
  });
}

processDir(process.cwd());
