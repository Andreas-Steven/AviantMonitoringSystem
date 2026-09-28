-- Soal 4 --

WITH RECURSIVE 
date_bounds AS (
    SELECT 
        MIN(DATE(actual_start)) AS min_dt,
        MAX(DATE(actual_start)) AS max_dt
    FROM production_result
),
date_series AS (
    SELECT min_dt AS dt, max_dt
    FROM date_bounds
    UNION ALL
    SELECT DATE_ADD(dt, INTERVAL 1 DAY), max_dt
    FROM date_series
    WHERE dt < max_dt
),
daily_stats AS (
    SELECT 
        DATE(pr.actual_start) AS prod_date,
        SUM(pr.good_qty) AS Total_Good,
        SUM(pr.reject_qty) AS Total_Reject,
        SUM(wo.target_qty) AS Total_Target
    FROM production_result pr
    JOIN work_order wo ON pr.wo_number = wo.wo_number
    GROUP BY DATE(pr.actual_start)
)
SELECT 
    ds.dt AS Date,
    COALESCE(st.Total_Good, 0) AS `Total Good`,
    COALESCE(st.Total_Reject, 0) AS `Total Reject`,
    CASE 
        WHEN COALESCE(st.Total_Target, 0) = 0 THEN 0 
        ELSE ROUND(st.Total_Good / st.Total_Target * 100) 
    END AS Achievement
FROM date_series ds
LEFT JOIN daily_stats st ON ds.dt = st.prod_date
ORDER BY ds.dt;