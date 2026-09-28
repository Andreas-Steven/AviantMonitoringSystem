-- Soal 4 --

WITH RECURSIVE 

Date_Bounds AS (
    SELECT 
        MIN(DATE(production_result.actual_start)) AS min_dt, 
        MAX(DATE(production_result.actual_start)) AS max_dt
    FROM production_result
),

Date_Series AS (
    SELECT 
        Date_Bounds.min_dt AS dt, 
        Date_Bounds.max_dt
    FROM Date_Bounds
    UNION ALL
    SELECT 
        DATE_ADD(Date_Series.dt, INTERVAL 1 DAY), 
        Date_Series.max_dt
    FROM Date_Series
    WHERE Date_Series.dt < Date_Series.max_dt
),

Daily_Stats AS (
    SELECT 
        DATE(production_result.actual_start) AS prod_date, 
        SUM(production_result.good_qty) AS total_good, 
        SUM(production_result.reject_qty) AS total_reject, 
        SUM(work_order.target_qty) AS total_target
    FROM production_result 
    JOIN work_order ON work_order.wo_number = production_result.wo_number
    GROUP BY DATE(production_result.actual_start)
)

SELECT 
    Date_Series.dt AS `Date`, 
    COALESCE(Daily_Stats.total_good, 0) AS `Total Good`, 
    COALESCE(Daily_Stats.total_reject, 0) AS `Total Reject`, 
    CASE 
        WHEN COALESCE(Daily_Stats.total_target, 0) = 0 THEN 0 
        ELSE ROUND(Daily_Stats.total_good / Daily_Stats.total_target * 100) 
    END AS `Achievement`
FROM Date_Series
LEFT JOIN Daily_Stats ON Daily_Stats.prod_date = Date_Series.dt
ORDER BY Date_Series.dt;