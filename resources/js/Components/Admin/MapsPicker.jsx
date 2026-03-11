import React, { useState, useEffect, useRef } from 'react';
import { MapContainer, TileLayer, Marker, Popup, useMap, useMapEvents } from 'react-leaflet';
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';
import { MapPin } from 'lucide-react';

// Fix for default marker icon in React Leaflet
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
    iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
});

// Component to handle click events on the map
function LocationMarker({ position, setPosition, setAddress }) {
    const map = useMapEvents({
        click(e) {
            setPosition(e.latlng);
            // Optionally fetch address from coordinates here if needed using a reverse geocoding service
        },
    });

    useEffect(() => {
        if (position) {
            map.flyTo(position, map.getZoom());
        }
    }, [position, map]);

    return position === null ? null : (
        <Marker position={position}>
            <Popup>Lokasi Sekolah</Popup>
        </Marker>
    );
}

export default function MapsPicker({ initialLat, initialLng, onChange }) {
    // Default to Indonesia or Bandung if no initial pos
    const [position, setPosition] = useState(
        initialLat && initialLng 
            ? { lat: parseFloat(initialLat), lng: parseFloat(initialLng) } 
            : { lat: -6.9175, lng: 107.6191 } // Bandung Default
    );

    useEffect(() => {
        if (position) {
            // Generate Google Maps URL
            const mapsUrl = `https://www.google.com/maps?q=${position.lat},${position.lng}`;
            // Generate Embed URL (simple version)
            const embedUrl = `https://maps.google.com/maps?q=${position.lat},${position.lng}&hl=id&z=16&output=embed`;

            onChange({
                lat: position.lat,
                lng: position.lng,
                mapsUrl,
                embedUrl
            });
        }
    }, [position]);

    return (
        <div className="space-y-3">
             <div className="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm text-blue-800 flex gap-2">
                <MapPin className="w-5 h-5 flex-shrink-0" />
                <p>Klik pada peta untuk menandai lokasi sekolah. Link Google Maps akan terisi otomatis.</p>
            </div>
            
            <div className="h-[400px] w-full rounded-xl overflow-hidden shadow border border-gray-300 z-0">
                <MapContainer 
                    center={position} 
                    zoom={15} 
                    style={{ height: '100%', width: '100%' }}
                    scrollWheelZoom={true}
                >
                    <TileLayer
                        attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                    />
                    <LocationMarker position={position} setPosition={setPosition} />
                </MapContainer>
            </div>
            
            <div className="grid grid-cols-2 gap-4 text-xs text-gray-500">
                <div>Latitude: {position.lat.toFixed(6)}</div>
                <div>Longitude: {position.lng.toFixed(6)}</div>
            </div>
        </div>
    );
}
