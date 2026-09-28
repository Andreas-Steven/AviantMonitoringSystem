-- Soal 1 --

WITH operator AS (
    SELECT 
        work_order.product_code, 
        work_order.employee_no, 
        COUNT(*) AS total_wo
    FROM work_order 
    GROUP BY work_order.product_code, work_order.employee_no
)

SELECT 
    product.product_name AS `Product`, 
    COALESCE(SUM(operator.total_wo), 0) AS `Total WO`, 
    COUNT(operator.employee_no) AS `Total Operator`,
    COALESCE(
        GROUP_CONCAT(employee.full_name ORDER BY employee.full_name, employee.employee_no SEPARATOR ', '), ''
    ) AS `Nama Operator`
    
FROM product
LEFT JOIN operator ON operator.product_code = product.product_code
LEFT JOIN employee ON employee.employee_no = operator.employee_no
GROUP BY product.product_code, product.product_name
ORDER BY `Total Operator` DESC, product.product_name, product.product_code;