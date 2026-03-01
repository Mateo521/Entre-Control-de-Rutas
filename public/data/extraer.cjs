const fs = require('fs');

const rawData = fs.readFileSync('argentina.geojson');
const geojson = JSON.parse(rawData);

const sanLuisFeatures = geojson.features.filter(feature => 
    feature.properties.nam === 'San Luis' || 
    feature.properties.fna === 'Provincia de San Luis'
);

if (sanLuisFeatures.length === 0) {
    console.error('No se encontró la provincia de San Luis en el archivo.');
    process.exit(1);
}

const sanLuisGeoJSON = {
    type: "FeatureCollection",
    features: sanLuisFeatures
};

fs.writeFileSync('san_luis_limites.geojson', JSON.stringify(sanLuisGeoJSON));

console.log('Operación exitosa. El nuevo archivo ha sido guardado en public/data/san_luis_limites.geojson');