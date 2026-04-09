import mysql from "mysql2/promise";

// Cron schedule: * * * * * (every 1 minute)
// Creates a challan after exactly 3 consecutive minutes of high pollution
// (pollution_value > 100), then resets both minutes_count and violation_count.

const pool = mysql.createPool({
  host: Bun.env.DB_HOST,
  user: Bun.env.DB_USER,
  password: Bun.env.DB_PASSWORD,
  database: Bun.env.DB_NAME,
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0,
});

async function checkViolationsAndCreateChallans() {
  const conn = await pool.getConnection();

  try {
    // Step 1: Get all active violations where pollution is high (> 100)
    const [violations] = await conn.query<any[]>(
      `SELECT v.id, v.vehicle_id, v.violation_count, v.minutes_count,
              ve.vehicle_number, ve.owner_email
       FROM violations v
       JOIN vehicles ve ON v.vehicle_id = ve.id
       WHERE v.pollution_value > 100`
    );

    console.log(`Found ${violations.length} vehicles with pollution_value > 100`);

    let challansCreated = 0;

    for (const violation of violations) {
      const {
        vehicle_id,
        violation_count,
        minutes_count,
        vehicle_number,
      } = violation;

      const newMinutesCount = minutes_count + 1;
      console.log(
        `Vehicle ${vehicle_number}: minutes_count: ${minutes_count} → ${newMinutesCount}`
      );

      if (newMinutesCount === 3) {
        // Exactly 3 minutes of sustained high pollution — create challan

        // Check previous unpaid challans to scale amount
        const [previousChallans] = await conn.query<any[]>(
          `SELECT COUNT(*) as count FROM challans WHERE vehicle_id = ? AND status = 'unpaid'`,
          [vehicle_id]
        );

        const prevCount = previousChallans[0]?.count || 0;
        const baseAmount = 500;
        const additionalAmount = prevCount * 250;
        const totalAmount = baseAmount + additionalAmount;
        const challanCount = 1 + prevCount;

        console.log(
          `Vehicle ${vehicle_number}: ${prevCount} previous unpaid challans, ` +
            `count=${challanCount}, amount=₹${totalAmount}`
        );

        // Insert challan record
        await conn.query(
          `INSERT INTO challans (vehicle_id, amount, status, violation_count, count, challan_date, updated_at)
           VALUES (?, ?, 'unpaid', ?, ?, NOW(), NOW())`,
          [vehicle_id, totalAmount, violation_count, challanCount]
        );

        // Reset BOTH minutes_count AND violation_count to 0
        await conn.query(
          `UPDATE violations
           SET minutes_count = 0, violation_count = 0, violation_date = NOW()
           WHERE vehicle_id = ?`,
          [vehicle_id]
        );

        console.log(
          `✓ CHALLAN CREATED + RESET — Vehicle ${vehicle_number} (ID: ${vehicle_id}) ` +
            `Count: ${challanCount}, Amount: ₹${totalAmount} | minutes_count → 0, violation_count → 0`
        );

        challansCreated++;
      } else {
        // Less than 3 minutes — just increment minutes_count
        await conn.query(
          `UPDATE violations SET minutes_count = ?, violation_date = NOW() WHERE vehicle_id = ?`,
          [newMinutesCount, vehicle_id]
        );

        console.log(
          `  minutes_count updated to ${newMinutesCount} for ${vehicle_number}`
        );
      }
    }

    return {
      status: "success",
      challansCreated,
      message: `Processed ${violations.length} high-pollution violations. ${challansCreated} challan(s) created.`,
    };
  } catch (error) {
    console.error("Error in violation checker:", error);
    return {
      status: "error",
      message: error instanceof Error ? error.message : "Unknown error",
    };
  } finally {
    await conn.release();
  }
}

// Run the check (triggered every 1 minute via cron: * * * * *)
const result = await checkViolationsAndCreateChallans();
console.log(JSON.stringify(result, null, 2));
