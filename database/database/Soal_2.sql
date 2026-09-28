-- Soal 2 --

WITH product_agg AS (
    SELECT w.product_code,
           COUNT(DISTINCT w.wo_number) AS total_wo,
           SUM(w.target_qty)           AS target,
           SUM(r.good_qty)             AS good_qty,
           SUM(r.reject_qty)           AS reject_qty
    FROM work_order w
    JOIN production_result r ON r.wo_number = w.wo_number
    GROUP BY w.product_code
)
SELECT p.product_name AS Product, a.total_wo AS `Total WO`, a.target AS Target,
       a.good_qty AS `Good Qty`, a.reject_qty AS `Reject Qty`,
       FLOOR(a.good_qty / a.target * 100) AS `Achievement (%)`
FROM product_agg a
JOIN product p ON p.product_code = a.product_code
ORDER BY a.good_qty / a.target DESC, p.product_name;