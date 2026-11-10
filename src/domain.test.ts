import {describe,it,expect} from 'vitest';
import {seed} from './seed';
import {receive,reportException,resolveException,outstanding,receivingStatus} from './domain';
import {parseManifest} from './import';
describe('receiving invariants',()=>{
 it('checks in one parcel without changing customs clearance',()=>{const s=seed()[0];const next=receive(s,'MW-1048-P009');expect(outstanding(next)).toBe(outstanding(s)-1);expect(next.clearance).toBe(s.clearance);expect(s.parcels[8].received).toBe(false);expect(next.events[0].detail).toBe('MW-1048-P009')});
 it('rejects duplicate and unexpected scans without counting them',()=>{const s=seed()[0];expect(()=>receive(s,'MW-1048-P001')).toThrow('already');expect(()=>receive(s,'UNKNOWN')).toThrow('not on the manifest');expect(outstanding(s)).toBe(4)});
 it('keeps complete but customs-held shipments held',()=>{const s=seed()[1];expect(receivingStatus(s)).toBe('Received');expect(s.clearance).toBe('Held')});
 it('records and resolves damage without receiving the parcel automatically',()=>{const s=seed()[0];const flagged=reportException(s,'MW-1048-P009','Damage','Crushed carton');expect(flagged.parcels[8].damaged).toBe(true);expect(flagged.parcels[8].received).toBe(false);const resolved=resolveException(flagged,flagged.exceptions[0].id);expect(resolved.exceptions[0].resolved).toBe(true);expect(resolved.exceptions).toHaveLength(1);expect(resolved.clearance).toBe('Pending')});
 it('rejects duplicate open discrepancies and empty notes',()=>{const s=seed()[1];expect(()=>reportException(s,'MW-1049-P003','Damage','Again')).toThrow('already');expect(()=>reportException(s,'MW-1049-P003','Damage',' ')).toThrow('note')});
});
describe('manifest imports',()=>{
 const header='shipment_id,customer,origin,carrier,eta,bay,parcel_id,description\n';
 it('parses quoted commas without corrupting parcel descriptions',()=>{const s=parseManifest(header+'MW-2000,Customer,France,Carrier,12:00,Bay 1,P-1,"Textiles, cotton"',[]);expect(s.parcels[0].description).toBe('Textiles, cotton');expect(s.clearance).toBe('Pending')});
 it('rejects duplicate parcel labels and mixed shipment metadata',()=>{expect(()=>parseManifest(header+'MW-2,A,B,C,D,E,P-1,F\nMW-2,A,B,C,D,E,P-1,F',[])).toThrow('unique');expect(()=>parseManifest(header+'MW-2,A,B,C,D,E,P-1,F\nMW-3,A,B,C,D,E,P-2,F',[])).toThrow('same shipment')});
});
