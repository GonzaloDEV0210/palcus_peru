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
      
      // Fix double URLs like https://res.cloudinary...https://res.cloudinary...
      const regex = /(https:\/\/res\.cloudinary\.com\/[^\/]+\/image\/upload\/[^\/]+\/palcus_)(https:\/\/res\.cloudinary\.com\/)/g;
      
      if (regex.test(content)) {
        content = content.replace(regex, '$2');
        fs.writeFileSync(fullPath, content);
        console.log(`Cleaned: ${fullPath}`);
      }
    }
  });
}

processDir(process.cwd());
