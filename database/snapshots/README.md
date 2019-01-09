# Glazy Database Snapshot

## Field definitions:

 * id: Material/Recipe ID in Glazy
 * name: Name of material
 * created_by_user_id: The user ID in Glazy
 * material_type_id: Categorizes the material, e.g. "Celadon".  
 * material_type: Name of the material category.  May have duplicates, as material_type is a tree.  e.g. "Celadon -> Blue" and "Blue"
 * rgb_r, rgb_g, rgb_b: RGB values that represent the color of the glaze.  Usually taken from a photo.  Not reliable.
 * surface_type:
   - 1	Matte
   - 2	Matte - Stony
   - 3	Matte - Dry
   - 4	Matte - Semi
   - 5	Matte - Smooth
   - 6	Satin
   - 7	Satin - Matte
   - 8	Glossy
   - 9	Glossy - Semi
 * transparency_type:
   - 1	Opaque
   - 2	Semi-opaque
   - 3	Translucent
   - 4	Transparent
 * from_orton_cone, to_orton_cone: The temperature range for the material
 * is_analysis: If true, this is simply a chemical analysis, for example an analysis of a Song Dynasty celadon glaze.
 * is_primitive: Primitive materials are actual materials such as EPK, Whiting, Silica, etc.
 * is_theoretical: Theoretical materials are primitive materials like Kaolin and Potash Feldspar that are based on formulas.
 * is_private: If true, only visible to the user who created this material.
 * SiO2_percent, Al2O3_percent, B2O3_percent, etc.: Percentage analysis
 * Al2O3_umf, B2O3_umf, etc.:  UMF analysis
 * Al2O3_percent_mol, B2O3_percent_mol, etc: Percent Mol analysis
 * SiO2_Al2O3_ratio_umf: Silica Alumina ratio
 * R2O_umf, RO_umf: Totals for R2O and RO values in UMF
 * loi: Loss on Ignition
 