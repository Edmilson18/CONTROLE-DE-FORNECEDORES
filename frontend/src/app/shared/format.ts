import { AbstractControl, ValidationErrors } from '@angular/forms';
export function cnpjMask(value: string): string {
  const raw = value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 14);
  let output = '';
  for (let i = 0; i < raw.length; i++) {
    if (i === 2 || i === 5) { output += '.'; }
    if (i === 8) { output += '/'; }
    if (i === 12) { output += '-'; }
    output += raw[i];
  }
  return output;
}
export function phoneMask(value: string): string {
  const raw = value.replace(/\D/g, '').slice(0, 11);
  if (!raw) { return ''; }
  if (raw.length < 3) { return '(' + raw; }
  const local = raw.slice(2); const split = raw.length > 10 ? 5 : 4;
  return `(${raw.slice(0, 2)}) ${local.slice(0, split)}` + (local.length > split ? '-' + local.slice(split) : '');
}
export function validCnpj(value: string): boolean {
  const raw = value.toUpperCase().replace(/[./\s-]/g, '');
  if (!/^[A-Z0-9]{12}\d{2}$/.test(raw) || /^(.)\1{13}$/.test(raw)) { return false; }
  let base = raw.slice(0, 12);
  for (let digit = 0; digit < 2; digit++) {
    let sum = 0; let weight = 2;
    for (let i = base.length - 1; i >= 0; i--) { sum += (base.charCodeAt(i) - 48) * weight; weight = weight === 9 ? 2 : weight + 1; }
    const remainder = sum % 11; base += remainder < 2 ? '0' : String(11 - remainder);
  }
  return base === raw;
}
export function cnpjValidator(control: AbstractControl): ValidationErrors | null { return !control.value || validCnpj(String(control.value)) ? null : {cnpj: true}; }
export function localDate(): string {
  const date = new Date(); return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}
