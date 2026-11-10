import type { Shipment } from './domain';
export const seed = ():Shipment[] => [
 ['MW-1048','Nordic Living','Copenhagen, DK','Northline Logistics','09:30','Bay 03','Pending',12,8],
 ['MW-1049','Atelier Home','Lyon, FR','Eurofreight','10:15','Bay 01','Held',8,8],
 ['MW-1050','Studio Supply','Rotterdam, NL','Northline Logistics','11:00','Bay 04','Cleared',16,0],
 ['MW-1051','Field & Form','Dublin, IE','Greenway Transport','11:45','Bay 02','Pending',10,0],
 ['MW-1046','Everyday Objects','Hamburg, DE','Eurofreight','08:00','Bay 01','Cleared',6,6],
].map(([id,customer,origin,carrier,eta,bay,clearance,count,received],index)=>({
 id:String(id),customer:String(customer),origin:String(origin),carrier:String(carrier),eta:String(eta),bay:String(bay),clearance:clearance as Shipment['clearance'],
 parcels:Array.from({length:Number(count)},(_,i)=>({id:`${id}-P${String(i+1).padStart(3,'0')}`,description:['Homewares','Textiles','Accessories'][i%3],received:i<Number(received),damaged:index===1&&i===2})),
 exceptions:index===1?[{id:'EX-001',parcelId:`${id}-P003`,type:'Damage',note:'Outer carton crushed on arrival. Contents require inspection.',resolved:false,createdAt:'2026-11-10T09:10:00Z'}]:[],
 events:[{id:`EV-${id}`,at:'2026-11-10T08:00:00Z',action:'Manifest imported',detail:`${count} parcels expected from ${origin}`,actor:'PoC coordinator'}],
}));

