-- Soal 3 --

WITH machine_agg AS (
    SELECT m.machine_code, m.machine_name,
           SUM(r.good_qty)   AS total_good,
           SUM(r.reject_qty) AS total_reject,
           SUM(w.target_qty) AS total_target
    FROM production_result r
    JOIN work_order w ON w.wo_number    = r.wo_number
    JOIN machine    m ON m.machine_code = w.machine_code
    GROUP BY m.machine_code, m.machine_name
),
machine_ach AS (
    SELECT *, ROUND(total_good / total_target * 100) AS achievement
    FROM machine_agg
)
SELECT machine_name AS Machine, total_good AS `Total Good`,
       total_reject AS `Total Reject`, achievement AS Achievement,
       DENSE_RANK() OVER (ORDER BY achievement DESC) AS Ranking
FROM machine_ach
ORDER BY Ranking, Machine;