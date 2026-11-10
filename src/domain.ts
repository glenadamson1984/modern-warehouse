export type Clearance = 'Pending' | 'Held' | 'Cleared';
export type Parcel = { id: string; description: string; received: boolean; damaged: boolean };
export type Exception = { id: string; parcelId: string; type: 'Damage' | 'Missing' | 'Unexpected'; note: string; resolved: boolean; createdAt: string };
export type Event = { id: string; at: string; action: string; detail: string; actor: string };
export type Shipment = { id: string; customer: string; origin: string; carrier: string; eta: string; bay: string; clearance: Clearance; parcels: Parcel[]; exceptions: Exception[]; events: Event[] };
export const receivingStatus = (s: Shipment) => s.parcels.every(p => p.received) ? 'Received' : s.parcels.some(p => p.received) ? 'In progress' : 'Expected';
export const outstanding = (s: Shipment) => s.parcels.filter(p => !p.received).length;
export function receive(s: Shipment, parcelId: string, at = new Date().toISOString()): Shipment {
 const parcel = s.parcels.find(p => p.id === parcelId.trim());
 if (!parcel) throw new Error('This parcel is not on the manifest. Check the label or record an unexpected parcel.');
 if (parcel.received) throw new Error('This parcel has already been checked in. It has not been counted twice.');
 return {...s, parcels:s.parcels.map(p=>p.id===parcel.id?{...p,received:true}:p),events:[{id:crypto.randomUUID(),at,action:'Parcel received',detail:parcel.id,actor:'Demo operator'},...s.events]};
}
export function reportException(s:Shipment, parcelId:string, type:Exception['type'], note:string):Shipment {
 if(!note.trim())throw new Error('Add a note explaining the discrepancy.');
 if(type!=='Unexpected'&&!s.parcels.some(p=>p.id===parcelId))throw new Error('Choose a parcel on this manifest.');
 if(s.exceptions.some(e=>e.parcelId===parcelId&&e.type===type&&!e.resolved))throw new Error('An open discrepancy of this type already exists for this parcel.');
 const at=new Date().toISOString();
 return {...s,parcels:s.parcels.map(p=>p.id===parcelId&&type==='Damage'?{...p,damaged:true}:p),exceptions:[{id:crypto.randomUUID(),parcelId,type,note:note.trim(),resolved:false,createdAt:at},...s.exceptions],events:[{id:crypto.randomUUID(),at,action:`${type} reported`,detail:`${parcelId}: ${note.trim()}`,actor:'Demo operator'},...s.events]};
}
export function resolveException(s:Shipment,id:string):Shipment {
 const item=s.exceptions.find(e=>e.id===id);if(!item||item.resolved)throw new Error('This discrepancy is no longer open.');
 return {...s,exceptions:s.exceptions.map(e=>e.id===id?{...e,resolved:true}:e),events:[{id:crypto.randomUUID(),at:new Date().toISOString(),action:'Discrepancy resolved',detail:`${item.parcelId}: ${item.type}`,actor:'Demo operator'},...s.events]};
}
export const shipmentSummary = (s:Shipment) => {
 const missing=outstanding(s), open=s.exceptions.filter(e=>!e.resolved);
 return `${s.id}: ${s.parcels.filter(p=>p.received).length} of ${s.parcels.length} parcels received. ${missing} still expected. ${open.length} open ${open.length===1?'discrepancy':'discrepancies'}. Customs status: ${s.clearance.toLowerCase()}. ${missing?'Next: reconcile the remaining labels against the manifest.':open.length?'Next: review the reported discrepancies.':'Receiving is complete.'} Physical receipt does not change customs clearance.`;
};
