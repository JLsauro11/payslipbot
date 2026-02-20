<!DOCTYPE html>
<html>
<head>
    <title>Payslip Bot - Privacy Policy</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; line-height: 1.6; }
        h1 { color: #333; }
        h2 { color: #555; }
        ul { padding-left: 20px; }
        strong { color: #333; }
    </style>
</head>
<body>
<h1>Payslip Bot Privacy Policy</h1>

<p><strong>Effective Date:</strong> February 5, 2026</p>

<h2>1. What Information We Collect</h2>
<ul>
    <li><strong>Employee ID</strong> (e.g., EMP1001) - Used to lookup payslips</li>
    <li><strong>Facebook Messenger PSID</strong> - Temporary session identifier for conversation state</li>
    <li><strong>Payslip selections</strong> - Year (2025/2026), Month (Jan-Dec), Cutoff (First/Second)</li>
    <li><strong>Temporary cache data</strong> - Redis cache expires after 1 hour (3600 seconds)</li>
</ul>

<h2>2. How We Use Your Information</h2>
<ul>
    <li>Validate Employee ID against our Employee database</li>
    <li>Maintain conversation state across 4-step flow (ID → Year → Month → Cutoff)</li>
    <li>Query Payslip database using exact date format MM/DD/YYYY (e.g., "01/30/2026")</li>
    <li>Generate and deliver PDF payslip download links</li>
    <li>Temporary caching for faster response times</li>
</ul>

<h2>3. Data Storage & Retention</h2>
<ul>
    <li><strong>Cache data:</strong> Automatically expires after 1 hour</li>
    <li><strong>Database queries:</strong> No personal data stored beyond original Employee/Payslip tables</li>
    <li><strong>Logs:</strong> Debug logs contain PSID, Employee ID, dates (rotated daily)</li>
</ul>

<h2>4. What We DO NOT Do</h2>
<ul>
    <li>❌ Sell your data to third parties</li>
    <li>❌ Share with external companies</li>
    <li>❌ Store conversation history permanently</li>
    <li>❌ Track you outside Facebook Messenger</li>
</ul>

<h2>5. Data Security</h2>
<ul>
    <li>HTTPS encrypted communication with Facebook Graph API (v21.0)</li>
    <li>Redis cache with 1-hour TTL on all bot states</li>
    <li>Laravel encryption for sensitive environment variables (PAGE_ACCESS_TOKEN)</li>
</ul>

<h2>6. Facebook Platform Compliance</h2>
<ul>
    <li>Complies with <a href="https://developers.facebook.com/docs/messenger-platform/policy-overview/" target="_blank">Messenger Platform Policy</a></li>
    <li>Provides clear opt-out via "restart" command</li>
    <li>Responds within Facebook's responsiveness requirements</li>
</ul>

<h2>7. Your Rights</h2>
<ul>
    <li><strong>Restart bot:</strong> Type "restart" to clear all session data instantly</li>
    <li><strong>Cache auto-expiry:</strong> All data automatically deleted after 1 hour</li>
    <li><strong>Contact HR:</strong> For data access or deletion requests</li>
</ul>

<h2>8. Technical Details</h2>
<ul>
    <li><strong>State Management:</strong> Redis cache (`bot_state_{PSID}`) with 3600s TTL</li>
    <li><strong>Database:</strong> Employee & Payslip tables (read-only queries)</li>
    <li><strong>API:</strong> Facebook Graph API v21.0 (messages endpoint)</li>
    <li><strong>Format:</strong> Payslip dates stored as MM/DD/YYYY (01/30/2026)</li>
</ul>

<h2>9. Contact Information</h2>
<p>
    <strong>Email:</strong> itdept.redspeed@rs8.com.ph<br>
    <strong>Location:</strong> RS8 Software Development<br>
    <strong>Address:</strong> Calao East, City of Santiago, Isabela, Philippines
</p>

<hr>
<p style="font-size: 12px; color: #666; text-align: center;">
    Last updated: February 5, 2026 • RS8 Software Development
</p>
</body>
</html>
