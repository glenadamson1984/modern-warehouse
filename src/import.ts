import Papa from 'papaparse';
import type {Shipment} from './domain';
export function parseManifest(csv:string,existing:Shipment[]):Shipment {
 const parsed=Papa.parse<Record<string,string>>(csv,{header:true,skipEmptyLines:'greedy',transformHeader:h=>h.trim()});
 const required=['shipment_id','customer','origin','carrier','eta','bay','parcel_id','description'];
 if(parsed.errors.length||required.some(k=>!parsed.meta.fields?.includes(k)))throw new Error('Use the sample CSV format with all eight required columns.');
 if(!parsed.data.length||parsed.data.length>5000)throw new Error('Import between 1 and 5,000 parcel rows.');
 const row=parsed.data[0]; const id=row.shipment_id.trim();
 if(!/^[A-Za-z0-9-]{1,40}$/.test(id))throw new Error('Use a shipment reference of 1–40 letters, numbers or hyphens.');
 if(existing.some(s=>s.id===id))throw new Error('This manifest reference already exists.');
 const seen=new Set<string>();
 for(const r of parsed.data){if(required.some(k=>!r[k]?.trim())||required.slice(0,6).some(k=>r[k].trim()!==row[k].trim()))throw new Error('All rows must describe the same shipment and contain every required value.');if(seen.has(r.parcel_id.trim())||existing.some(s=>s.parcels.some(p=>p.id===r.parcel_id.trim())))throw new Error('Parcel labels must be unique across all shipments.');seen.add(r.parcel_id.trim())}
 return {id,customer:row.customer.trim(),origin:row.origin.trim(),carrier:row.carrier.trim(),eta:row.eta.trim(),bay:row.bay.trim(),clearance:'Pending',parcels:parsed.data.map(r=>({id:r.parcel_id.trim(),description:r.description.trim(),received:false,damaged:false})),exceptions:[],events:[{id:crypto.randomUUID(),at:new Date().toISOString(),action:'Manifest imported',detail:`${parsed.data.length} parcels imported from CSV`,actor:'Demo coordinator'}]};
}
