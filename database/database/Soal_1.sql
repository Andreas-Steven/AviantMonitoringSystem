-- SOAL 1 --

SELECT
    p.product_name AS product,
    COALESCE(SUM(po.total_wo), 0) AS total_wo,
    COUNT(po.employee_no) AS total_operator,
    COALESCE(
        GROUP_CONCAT(
            e.full_name
            ORDER BY e.full_name, e.employee_no
            SEPARATOR ', '
        ), ''
FROM product AS p
    ) AS nama_operator
LEFT JOIN (
    SELECT
        product_code,
        employee_no,
        COUNT(*) AS total_wo
    FROM work_order
    GROUP BY product_code, employee_no
) AS po ON po.product_code = p.product_code
LEFT JOIN employee AS e ON e.employee_no = po.employee_no
GROUP BY p.product_code, p.product_name
ORDER BY
    total_operator DESC,
    p.product_name ASC,
    p.product_code ASC;