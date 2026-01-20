// MongoDB Fixtures Script
// This script loads sample data into MongoDB for the UserActivity collection
// It can be executed using: mongosh "mongodb://user:pass@host:port/database" < load-fixtures.js

// Use the current database (already connected via mongosh command line)
// The entrypoint.sh script connects to the database before running this script
// If running directly without connection, uncomment the line below:
// use('anonymize_demo');

// Clear existing collection
db.user_activities.deleteMany({});

// Sample user activities data
const activities = [];

const actions = ['login', 'logout', 'view_page', 'update_profile', 'create_order', 'cancel_order', 'add_to_cart', 'remove_from_cart'];
const now = new Date();

// Generate 30 sample activities
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
        anonymized: false, // Track anonymization status (similar to AnonymizableTrait in ORM entities)
    });
}

// Insert activities
const result = db.user_activities.insertMany(activities);

print(`✅ Inserted ${result.insertedCount} user activities into MongoDB`);
print(`📊 Collection 'user_activities' now has ${db.user_activities.countDocuments()} documents`);
