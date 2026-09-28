-- Soal 3 --

WITH Machine_Aggregate AS (
    SELECT 
        machine.machine_code, 
        machine.machine_name, 
        SUM(production_result.good_qty) AS total_good, 
        SUM(production_result.reject_qty) AS total_reject, 
        SUM(work_order.target_qty) AS total_target
    FROM production_result 
    JOIN work_order ON work_order.wo_number = production_result.wo_number
    JOIN machine ON machine.machine_code = work_order.machine_code
    GROUP BY machine.machine_code, machine.machine_name
),

Machine_Achievement AS (
    SELECT 
        Machine_Aggregate.*, 
        ROUND(Machine_Aggregate.total_good / Machine_Aggregate.total_target * 100) AS achievement
    FROM Machine_Aggregate
)

SELECT 
    machine_name AS `Machine`, 
    total_good AS `Total Good`, 
    total_reject AS `Total Reject`, 
    achievement AS `Achievement`, 
    DENSE_RANK() OVER (ORDER BY achievement DESC) AS `Ranking`
FROM Machine_Achievement
ORDER BY `Ranking`, `Machine`;