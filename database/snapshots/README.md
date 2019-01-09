# Glazy Database Snapshot

## Field definitions:

 * id: Material/Recipe ID in Glazy
 * name: Name of material
 * created_by_user_id: The user ID in Glazy
 * material_type_id: Categorizes the material, e.g. "Celadon".  
 * material_type: Name of the material category.  May have duplicates, as material_type is a tree.  e.g. "Celadon -> Blue" and "Blue"
 * rgb_r, rgb_g, rgb_b: RGB values that represent the color of the glaze.  Usually taken from a photo.  Not reliable.
