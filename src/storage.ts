import {seed} from './seed';
import {receive,reportException,resolveException} from './domain';
import type {Shipment,Exception} from './domain';
const KEY='modern-warehouse-demo-v1';
const base=(import.meta as unknown as {env:Record<string,string>}).env.VITE_API_URL?.replace(/\/$/,'');
export const mode=base?'api':'demo';
async function request(path:string,body?:unknown):Promise<Shipment[]> {
 const r=await fetch(`${base}${path}`,{method:body?'POST':'GET',headers:{'Content-Type':'application/json',Accept:'application/json'},body:body?JSON.stringify(body):undefined});
 const j=await r.json();if(!r.ok)throw new Error(j.message||'The server could not complete the request.');return j.data;
}
export const load=async():Promise<Shipment[]>=>{if(base)return request('/shipments');try{const saved=localStorage.getItem(KEY);return saved?JSON.parse(saved):seed()}catch{return seed()}};
function save(items:Shipment[]){localStorage.setItem(KEY,JSON.stringify(items));return items}
export async function checkIn(items:Shipment[],id:string,parcelId:string){if(base)return request(`/shipments/${id}/receive`,{parcelId});return save(items.map(s=>s.id===id?receive(s,parcelId):s))}
export async function flag(items:Shipment[],id:string,parcelId:string,type:Exception['type'],note:string){if(base)return request(`/shipments/${id}/exceptions`,{parcelId,type,note});return save(items.map(s=>s.id===id?reportException(s,parcelId,type,note):s))}
export async function resolve(items:Shipment[],id:string,exceptionId:string){if(base)return request(`/shipments/${id}/exceptions/${exceptionId}/resolve`,{});return save(items.map(s=>s.id===id?resolveException(s,exceptionId):s))}
export async function importShipment(items:Shipment[],shipment:Shipment){if(base)return request('/shipments',{shipment});if(items.some(s=>s.id===shipment.id))throw new Error('This manifest reference already exists.');return save([shipment,...items])}
export async function reset(){if(base)throw new Error('Reset is only available in the browser demo.');return save(seed())}
