// MongoDB Fixtures Script
// This script loads sample data into MongoDB for all collections
// It can be executed using: mongosh "mongodb://user:pass@host:port/database" < load-fixtures.js

// Use the current database (already connected via mongosh command line)
// The entrypoint.sh script connects to the database before running this script
// If running directly without connection, uncomment the line below:
// use('anonymize_demo');

const now = new Date();

// ============================================
// 1. USER ACTIVITIES
// ============================================
db.user_activities.deleteMany({});
const activities = [];
const actions = ['login', 'logout', 'view_page', 'update_profile', 'create_order', 'cancel_order', 'add_to_cart', 'remove_from_cart'];

for (let i = 1; i <= 30; i++) {
    const timestamp = new Date(now);
    timestamp.setDate(timestamp.getDate() - Math.floor(Math.random() * 365));
    timestamp.setHours(Math.floor(Math.random() * 24));
    timestamp.setMinutes(Math.floor(Math.random() * 60));

    activities.push({
        userEmail: `user${i}@example.com`,
        userName: `User ${i}`,
        ipAddress: `${Math.floor(Math.random() * 255) + 1}.${Math.floor(Math.random() * 255) + 1}.${Math.floor(Math.random() * 255) + 1}.${Math.floor(Math.random() * 255) + 1}`,
        action: actions[Math.floor(Math.random() * actions.length)],
        timestamp: timestamp,
        metadata: {
            userAgent: `Mozilla/5.0 (Browser ${i})`,
            sessionId: `session_${Math.random().toString(36).substring(2, 15)}`,
            referrer: i % 2 === 0 ? 'https://example.com' : null,
        },
        anonymized: false,
    });
}
const activitiesResult = db.user_activities.insertMany(activities);
print(`✅ Inserted ${activitiesResult.insertedCount} user activities`);

// ============================================
// 2. CUSTOMER PROFILES
// ============================================
db.customer_profiles.deleteMany({});
const profiles = [];
const statuses = ['active', 'inactive', 'suspended'];
const companies = ['Acme Corp', 'Tech Solutions', 'Global Industries', 'Digital Services', 'Innovation Labs', null];

for (let i = 1; i <= 25; i++) {
    const createdAt = new Date(now);
    createdAt.setDate(createdAt.getDate() - Math.floor(Math.random() * 730));

    profiles.push({
        email: `customer${i}@example.com`,
        firstName: `Customer${i}`,
        lastName: `LastName${i}`,
        phone: `+34${600000000 + i}`,
        address: `${Math.floor(Math.random() * 200) + 1} Main Street, City ${i}`,
        company: companies[Math.floor(Math.random() * companies.length)],
        username: `customer${i}`,
        website: i % 3 === 0 ? `https://customer${i}.example.com` : null,
        age: Math.floor(Math.random() * 50) + 18,
        status: statuses[Math.floor(Math.random() * statuses.length)],
        createdAt: createdAt,
        anonymized: false,
    });
}
const profilesResult = db.customer_profiles.insertMany(profiles);
print(`✅ Inserted ${profilesResult.insertedCount} customer profiles`);

// ============================================
// 3. TRANSACTION LOGS
// ============================================
db.transaction_logs.deleteMany({});
const transactions = [];
const currencies = ['EUR', 'USD', 'GBP'];
const transactionStatuses = ['pending', 'completed', 'failed', 'refunded'];

for (let i = 1; i <= 20; i++) {
    const transactionDate = new Date(now);
    transactionDate.setDate(transactionDate.getDate() - Math.floor(Math.random() * 180));

    transactions.push({
        transactionId: `TXN-${Date.now()}-${i}`,
        customerEmail: `customer${i}@example.com`,
        iban: `ES${91 + i}2100041845020005133${i}`,
        creditCard: `${4000 + i}${1000 + i}${2000 + i}${3000 + i}`,
        maskedCard: `****-****-****-${3000 + i}`,
        amount: parseFloat((Math.random() * 1000 + 10).toFixed(2)),
        currency: currencies[Math.floor(Math.random() * currencies.length)],
        transactionHash: `hash_${i}_${Math.random().toString(36).substring(2, 15)}_${Math.random().toString(36).substring(2, 15)}`,
        status: transactionStatuses[Math.floor(Math.random() * transactionStatuses.length)],
        transactionDate: transactionDate,
        anonymized: false,
    });
}
const transactionsResult = db.transaction_logs.insertMany(transactions);
print(`✅ Inserted ${transactionsResult.insertedCount} transaction logs`);

// ============================================
// 4. DEVICE INFOS
// ============================================
db.device_infos.deleteMany({});
const devices = [];
const osVersions = ['Windows 11', 'macOS 14', 'Linux Ubuntu 22.04', 'iOS 17', 'Android 13'];
const browserVersions = ['Chrome 120.0', 'Firefox 121.0', 'Safari 17.0', 'Edge 120.0'];

function generateUUID() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        const r = Math.random() * 16 | 0;
        const v = c === 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}

for (let i = 1; i <= 15; i++) {
    const lastSeen = new Date(now);
    lastSeen.setDate(lastSeen.getDate() - Math.floor(Math.random() * 30));

    devices.push({
        deviceId: generateUUID(),
        ipAddress: `${192}.${168}.${Math.floor(Math.random() * 255) + 1}.${Math.floor(Math.random() * 255) + 1}`,
        macAddress: `${(Math.floor(Math.random() * 255)).toString(16).padStart(2, '0')}:${(Math.floor(Math.random() * 255)).toString(16).padStart(2, '0')}:${(Math.floor(Math.random() * 255)).toString(16).padStart(2, '0')}:${(Math.floor(Math.random() * 255)).toString(16).padStart(2, '0')}:${(Math.floor(Math.random() * 255)).toString(16).padStart(2, '0')}:${(Math.floor(Math.random() * 255)).toString(16).padStart(2, '0')}`,
        deviceHash: `hash_device_${i}_${Math.random().toString(36).substring(2, 15)}_${Math.random().toString(36).substring(2, 15)}`,
        location: `${(Math.random() * 180 - 90).toFixed(6)}, ${(Math.random() * 360 - 180).toFixed(6)}`,
        themeColor: `#${Math.floor(Math.random() * 16777215).toString(16).padStart(6, '0')}`,
        deviceName: `Device ${i}`,
        osVersion: osVersions[Math.floor(Math.random() * osVersions.length)],
        browserVersion: browserVersions[Math.floor(Math.random() * browserVersions.length)],
        isActive: Math.random() > 0.3,
        lastSeen: lastSeen,
        anonymized: false,
    });
}
const devicesResult = db.device_infos.insertMany(devices);
print(`✅ Inserted ${devicesResult.insertedCount} device infos`);

// ============================================
// 5. ANALYTICS EVENTS
// ============================================
db.analytics_events.deleteMany({});
const events = [];
const eventTypes = ['page_view', 'click', 'purchase', 'signup'];
const countries = ['ES', 'US', 'GB', 'FR', 'DE', 'IT'];
const languages = ['es-ES', 'en-US', 'en-GB', 'fr-FR', 'de-DE', 'it-IT'];
const categories = ['pending', 'processing', 'completed', 'failed'];

for (let i = 1; i <= 35; i++) {
    const timestamp = new Date(now);
    timestamp.setDate(timestamp.getDate() - Math.floor(Math.random() * 90));
    timestamp.setHours(Math.floor(Math.random() * 24));
    timestamp.setMinutes(Math.floor(Math.random() * 60));

    events.push({
        eventId: generateUUID(),
        eventType: eventTypes[Math.floor(Math.random() * eventTypes.length)],
        country: countries[Math.floor(Math.random() * countries.length)],
        language: languages[Math.floor(Math.random() * languages.length)],
        eventData: JSON.stringify({
            page: `/page/${i}`,
            referrer: i % 2 === 0 ? 'https://example.com' : null,
            sessionId: `session_${Math.random().toString(36).substring(2, 15)}`,
        }),
        description: `Analytics event ${i} description with some text content.`,
        userIdHash: `hash_user_${i}_${Math.random().toString(36).substring(2, 15)}_${Math.random().toString(36).substring(2, 15)}`,
        category: categories[Math.floor(Math.random() * categories.length)],
        dataClassification: 'ANONYMIZED',
        timestamp: timestamp,
        anonymized: false,
    });
}
const eventsResult = db.analytics_events.insertMany(events);
print(`✅ Inserted ${eventsResult.insertedCount} analytics events`);

// ============================================
// SUMMARY
// ============================================
print('\n📊 MongoDB Collections Summary:');
print(`  - user_activities: ${db.user_activities.countDocuments()} documents`);
print(`  - customer_profiles: ${db.customer_profiles.countDocuments()} documents`);
print(`  - transaction_logs: ${db.transaction_logs.countDocuments()} documents`);
print(`  - device_infos: ${db.device_infos.countDocuments()} documents`);
print(`  - analytics_events: ${db.analytics_events.countDocuments()} documents`);
print('\n✅ All MongoDB fixtures loaded successfully!');
