
ALTER TABLE llx_porcentaje_impuestos ADD CONSTRAINT fk_llx_c_chargesociales   FOREIGN KEY (fk_chargesocialesn) REFERENCES llx_c_chargesociales (id);

ALTER TABLE llx_facture ADD multicurrency_total_impuestos DOUBLE(24,8) NULL DEFAULT '0.00000000';

ALTER TABLE llx_facture_impuesto ADD importe DOUBLE(24,8) NULL DEFAULT '0.00000000';

ALTER TABLE llx_facture_fourn ADD multicurrency_total_impuestos DOUBLE(24,8) NULL DEFAULT '0.00000000';
