const fs = require('fs');
const path = require('path');

const replacements = [
  { old: 'https://res.cloudinary.com/dv7nmkmpm/image/upload/palcus_assets/icon_logo_white.png', new: 'https://res.cloudinary.com/dv7nmkmpm/image/upload/palcus_assets/icon_logo_white.png' },
  { old: 'https://res.cloudinary.com/dv7nmkmpm/image/upload/palcus_assets/icon_logo.png', new: 'https://res.cloudinary.com/dv7nmkmpm/image/upload/palcus_assets/icon_logo.png' },
  { old: 'https://res.cloudinary.com/dv7nmkmpm/image/upload/palcus_assets/logo_palcus.png', new: 'https://res.cloudinary.com/dv7nmkmpm/image/upload/palcus_assets/logo_palcus.png' },
  { old: 'https://res.cloudinary.com/dv7nmkmpm/image/upload/palcus_assets/logo_palcus.png', new: 'https://res.cloudinary.com/dv7nmkmpm/image/upload/palcus_assets/logo_palcus.png' }
];

function processDir(dir) {
  const files = fs.readdirSync(dir);
  files.forEach(file => {
    const fullPath = path.join(dir, file);
    if (fs.lstatSync(fullPath).isDirectory()) {
      if (file !== 'node_modules' && file !== '.git') processDir(fullPath);
    } else if (file.endsWith('.html') || file.endsWith('.js')) {
      let content = fs.readFileSync(fullPath, 'utf8');
      let changed = false;
      replacements.forEach(r => {
        if (content.includes(r.old)) {
          content = content.split(r.old).join(r.new);
          changed = true;
        }
      });
      if (changed) {
        fs.writeFileSync(fullPath, content);
        console.log(`Updated: ${fullPath}`);
      }
    }
  });
}

processDir(process.cwd());
