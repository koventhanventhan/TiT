const fs = require('fs');
const path = require('path');

const rootDir = path.resolve(__dirname, '..');

function replaceInFile(filePath, replacements) {
  if (!fs.existsSync(filePath)) return;
  let content = fs.readFileSync(filePath, 'utf8');
  let count = 0;
  for (const [pattern, replacement] of replacements) {
    if (pattern.test(content)) {
      content = content.replace(pattern, replacement);
      count++;
    }
  }
  if (count > 0) {
    fs.writeFileSync(filePath, content, 'utf8');
    console.log(`Updated (${count} replacements):`, path.relative(rootDir, filePath));
  }
}

const targets = ['laravel_api', ''];

for (const prefix of targets) {
  const base = prefix ? path.join(rootDir, prefix) : rootDir;

  // 1. dashboard-theme.css
  replaceInFile(path.join(base, 'src', 'components', 'shared', 'dashboard-theme.css'), [
    [/\.teacher-console-layout,\r?\n\.student-console-layout\s*\{[\s\S]*?--t-danger-bg:\s*#fef2f2;\r?\n\}/g, '/* Console tokens inherited from :root / [data-theme-preset] */'],
    [/var\(--t-primary,\s*#4f0bd9\)/g, 'var(--t-primary)'],
    [/var\(--t-primary-border,\s*#ddd6fe\)/g, 'var(--t-primary-border)'],
  ]);

  // 2. StudentRegistrationForm.css
  replaceInFile(path.join(base, 'src', 'components', 'StudentRegistrationForm.css'), [
    [/(\.tit-reg-overlay\s*\{)[\s\S]*?(--t-danger-bg:\s*#fef2f2;\r?\n)/g, '$1\n  /* Tokens inherited from :root / [data-theme-preset] */\n'],
    [/var\(--t-primary,\s*#4f0bd9\)/g, 'var(--t-primary)'],
    [/var\(--t-primary-hover,\s*#4308b8\)/g, 'var(--t-primary-hover)'],
    [/#8b5cf6/g, 'var(--t-primary-border)'],
  ]);

  // 3. index.css
  replaceInFile(path.join(base, 'src', 'index.css'), [
    [/--primary-color:\s*#4f0bd9;/g, '--primary-color: var(--t-primary);'],
    [/--primary-dark:\s*#1a103c;/g, '--primary-dark: var(--t-sidebar);'],
    [/--primary-light:\s*#6d28d9;/g, '--primary-light: var(--t-primary-soft);'],
    [/--secondary-color:\s*#7c3aed;/g, '--secondary-color: var(--t-primary-hover);'],
    [/--accent-color:\s*#8b5cf6;/g, '--accent-color: var(--t-primary);'],
    [/--accent-glow:\s*rgba\(79,\s*11,\s*217,\s*0\.35\);/g, '--accent-glow: var(--shadow-glow);'],
    [/--bg-light:\s*#f5f3ff;/g, '--bg-light: var(--t-primary-soft);'],
    [/--bg-navy:\s*#1a103c;/g, '--bg-navy: var(--t-sidebar);'],
    [/--gradient-1:\s*linear-gradient\(90deg,\s*#1a103c\s*0%,\s*#4f0bd9\s*100%\);/g, '--gradient-1: var(--gradient-1);'],
    [/--gradient-2:\s*linear-gradient\(135deg,\s*#4f0bd9\s*0%,\s*#8b5cf6\s*100%\);/g, '--gradient-2: var(--gradient-2);'],
    [/--gradient-3:\s*linear-gradient\(135deg,\s*#4f0bd9\s*0%,\s*#4f0bd9\s*100%\);/g, '--gradient-3: var(--gradient-3);'],
    [/--gradient-hero:\s*linear-gradient\(90deg,\s*#1a103c\s*0%,\s*#4f0bd9\s*100%\);/g, '--gradient-hero: var(--gradient-hero);'],
    [/--shadow-glow:\s*0\s*0\s*1\.875rem\s*rgba\(79,\s*11,\s*217,\s*0\.2\);/g, '--shadow-glow: var(--shadow-glow);'],
    [/\/\*\s*background:\s*#1a103c\s*!important;\s*\*\//g, ''],
    [/var\(--t-primary,\s*#4f0bd9\)/g, 'var(--t-primary)'],
  ]);

  // 4. Hero.css & Hero.jsx
  replaceInFile(path.join(base, 'src', 'components', 'Hero.css'), [
    [/linear-gradient\(135deg,\s*#4f0bd9\s*0%,\s*#1a103c\s*100%\)/g, 'var(--gradient-hero)'],
  ]);
  replaceInFile(path.join(base, 'src', 'components', 'Hero.jsx'), [
    [/style=\{\{\s*fontSize:\s*'1\.5rem',\s*color:\s*'#4f0bd9'\s*\}\}/g, "style={{ fontSize: '1.5rem', color: 'var(--t-primary)' }}"],
    [/style=\{\{\s*fontSize:\s*'1\.75rem',\s*color:\s*'#4f0bd9'\s*\}\}/g, "style={{ fontSize: '1.75rem', color: 'var(--t-primary)' }}"],
  ]);

  // 5. StudentToolkit.jsx & StudentsParentsLoveUs.css
  replaceInFile(path.join(base, 'src', 'components', 'StudentToolkit.jsx'), [
    [/color:\s*'#4f0bd9'/g, "color: 'var(--t-primary)'"],
  ]);
  replaceInFile(path.join(base, 'src', 'components', 'StudentsParentsLoveUs.css'), [
    [/color:\s*#4f0bd9;/g, 'color: var(--t-primary);'],
  ]);

  // 6. SuperAdminOverview.css
  replaceInFile(path.join(base, 'src', 'pages', 'super-admin', 'SuperAdminOverview.css'), [
    [/background:\s*#f5f3ff;/g, 'background: var(--t-primary-soft);'],
  ]);

  // 7. AnimatedAuth.css
  replaceInFile(path.join(base, 'src', 'components', 'AnimatedAuth.css'), [
    [/var\(--primary-color,\s*#4f0bd9\)/g, 'var(--primary-color)'],
    [/linear-gradient\(90deg,\s*#1a103c\s*0%,\s*#4f0bd9\s*100%\)/g, 'var(--gradient-1)'],
    [/linear-gradient\(0deg,\s*transparent,\s*#1a103c,\s*transparent,\s*#4f0bd9\)/g, 'linear-gradient(0deg, transparent, var(--t-sidebar), transparent, var(--t-primary))'],
    [/linear-gradient\(0deg,\s*transparent,\s*#7c3aed,\s*transparent,\s*#8b5cf6\)/g, 'linear-gradient(0deg, transparent, var(--t-primary), transparent, var(--t-primary-border))'],
    [/linear-gradient\(0deg,\s*transparent,\s*#8b5cf6,\s*transparent,\s*#8b5cf6\)/g, 'linear-gradient(0deg, transparent, var(--t-primary-border), transparent, var(--t-primary-border))'],
    [/linear-gradient\(135deg,\s*#8b5cf6\s*0%,\s*#7c3aed\s*100%\)/g, 'var(--gradient-2)'],
  ]);

  // 8. StudentStudyAssistant.css
  replaceInFile(path.join(base, 'src', 'components', 'StudentStudyAssistant.css'), [
    [/linear-gradient\(135deg,\s*#4f46e5\s*0%,\s*#7c3aed\s*100%\)/g, 'var(--gradient-hero)'],
  ]);

  // 9. AboutPage.css
  replaceInFile(path.join(base, 'src', 'pages', 'AboutPage.css'), [
    [/linear-gradient\(135deg,\s*#4f0bd9\s*0%,\s*#7c3aed\s*100%\)/g, 'var(--gradient-1)'],
    [/background:\s*#8b5cf6;/g, 'background: var(--t-primary);'],
  ]);

  // 10. TutorApplyPage.css
  replaceInFile(path.join(base, 'src', 'pages', 'TutorApplyPage.css'), [
    [/linear-gradient\(to\s+right,\s*#2563eb,\s*#7c3aed\)/g, 'var(--gradient-1)'],
    [/linear-gradient\(to\s+right,\s*#1d4ed8,\s*#6d28d9\)/g, 'var(--gradient-hero)'],
  ]);

  // 11. StudentMaterials.jsx & TeacherMaterials.jsx
  for (const f of ['StudentMaterials.jsx', 'TeacherMaterials.jsx']) {
    replaceInFile(path.join(base, 'src', 'pages', f.startsWith('Student') ? 'student' : 'teacher', f), [
      [/var\(--t-primary-soft,\s*#f5f3ff\)/g, 'var(--t-primary-soft)'],
      [/var\(--t-primary-border,\s*#ddd6fe\)/g, 'var(--t-primary-border)'],
      [/var\(--t-primary,\s*#4f0bd9\)/g, 'var(--t-primary)'],
    ]);
  }

  // 12. StudentSchedule.jsx
  replaceInFile(path.join(base, 'src', 'pages', 'student', 'StudentSchedule.jsx'), [
    [/var\(--t-primary,\s*#4f0bd9\)/g, 'var(--t-primary)'],
  ]);

  // 13. ExamResultsPage.css
  replaceInFile(path.join(base, 'src', 'pages', 'ExamResultsPage.css'), [
    [/linear-gradient\(135deg,\s*#4a0e8f\s*0%,\s*#7c3aed\s*40%,\s*#a855f7\s*70%,\s*#c084fc\s*100%\)/g, 'var(--gradient-hero)'],
    [/linear-gradient\(135deg,\s*#7c3aed,\s*#a855f7\)/g, 'var(--gradient-2)'],
    [/linear-gradient\(135deg,\s*#4a0e8f,\s*#7c3aed\)/g, 'var(--gradient-1)'],
    [/linear-gradient\(135deg,\s*#f5f3ff,\s*#ede9fe\)/g, 'var(--t-primary-soft)'],
    [/linear-gradient\(90deg,\s*#7c3aed,\s*#a855f7\)/g, 'var(--gradient-2)'],
    [/border-color:\s*#7c3aed;/g, 'border-color: var(--t-primary);'],
    [/color:\s*#7c3aed;/g, 'color: var(--t-primary);'],
  ]);

  // 14. ClassesPage.css
  replaceInFile(path.join(base, 'src', 'pages', 'ClassesPage.css'), [
    [/var\(--accent-color,\s*#7c3aed\)/g, 'var(--accent-color)'],
  ]);

  // 15. sidebar-shell.css
  replaceInFile(path.join(base, 'src', 'components', 'shared', 'sidebar-shell.css'), [
    [/var\(--t-primary,\s*#4f0bd9\)/g, 'var(--t-primary)'],
  ]);

  // 16. StudentPaymentModule.css
  replaceInFile(path.join(base, 'src', 'components', 'student', 'StudentPaymentModule.css'), [
    [/var\(--t-primary,\s*#4f0bd9\)/g, 'var(--t-primary)'],
    [/var\(--t-primary-hover,\s*#4308b8\)/g, 'var(--t-primary-hover)'],
    [/var\(--t-primary-soft,\s*#f5f3ff\)/g, 'var(--t-primary-soft)'],
  ]);

  // 17. SubjectUpdateModal.jsx
  replaceInFile(path.join(base, 'src', 'components', 'student', 'SubjectUpdateModal.jsx'), [
    [/var\(--t-primary,\s*#4f0bd9\)/g, 'var(--t-primary)'],
  ]);

  // 18. NotesPage.css, PastPapersPage.css, RecordingsPage.css
  for (const pageName of ['NotesPage.css', 'PastPapersPage.css', 'RecordingsPage.css']) {
    replaceInFile(path.join(base, 'src', 'pages', pageName), [
      [/\.icon-email\s*\{\s*border-color:\s*#a78bfa;\s*color:\s*#7c3aed;\s*\}/g, '.icon-email { border-color: var(--t-primary-border); color: var(--t-primary); }'],
      [/\.icon-email:hover\s*\{\s*border-color:\s*#7c3aed;\s*background:\s*rgba\(124,\s*58,\s*237,\s*0\.06\);\s*\}/g, '.icon-email:hover { border-color: var(--t-primary); background: var(--t-primary-soft); }'],
      [/\.icon-location\s*\{\s*border-color:\s*#a78bfa;\s*color:\s*#7c3aed;\s*\}/g, '.icon-location { border-color: var(--t-primary-border); color: var(--t-primary); }'],
      [/\.icon-location:hover\s*\{\s*border-color:\s*#7c3aed;\s*background:\s*rgba\(124,\s*58,\s*237,\s*0\.06\);\s*\}/g, '.icon-location:hover { border-color: var(--t-primary); background: var(--t-primary-soft); }'],
    ]);
  }

  // 19. ContactPage.css
  replaceInFile(path.join(base, 'src', 'pages', 'ContactPage.css'), [
    [/linear-gradient\(135deg,\s*#1a103c\s*0%,\s*#2e1065\s*100%\)/g, 'var(--gradient-1)'],
  ]);

  // 20. WhyChooseUs.jsx
  replaceInFile(path.join(base, 'src', 'components', 'WhyChooseUs.jsx'), [
    [/color:\s*'#4f0bd9'/g, "color: 'var(--t-primary)'"],
    [/color:\s*'#8b5cf6'/g, "color: 'var(--t-primary)'"],
  ]);

  // 21. MonthlyPaymentRulesCard.jsx
  replaceInFile(path.join(base, 'src', 'components', 'student', 'MonthlyPaymentRulesCard.jsx'), [
    [/iconColor:\s*'#8b5cf6'/g, "iconColor: 'var(--t-primary)'"],
  ]);
}

console.log('Done additional token unification.');
