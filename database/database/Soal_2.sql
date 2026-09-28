-- Soal 2 --

WITH Product_Aggregate AS (
    SELECT 
        work_order.product_code, 
        COUNT(DISTINCT work_order.wo_number) AS total_wo, 
        SUM(work_order.target_qty) AS target, 
        SUM(production_result.good_qty) AS good_qty, 
        SUM(production_result.reject_qty) AS reject_qty
    FROM work_order 
    JOIN production_result ON production_result.wo_number = work_order.wo_number
    GROUP BY work_order.product_code
)

SELECT 
    product.product_name AS `Product`, 
    Product_Aggregate.total_wo AS `Total WO`, 
    Product_Aggregate.target AS `Target`, 
    Product_Aggregate.good_qty AS `Good Qty`, 
    Product_Aggregate.reject_qty AS `Reject Qty`, 
    FLOOR(Product_Aggregate.good_qty / Product_Aggregate.target * 100) AS `Achievement (%)`
    
FROM Product_Aggregate
JOIN product ON product.product_code = Product_Aggregate.product_code
ORDER BY Product_Aggregate.good_qty / Product_Aggregate.target DESC, product.product_name;