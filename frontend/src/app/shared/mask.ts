import { Directive, ElementRef, forwardRef, HostListener, inject, input } from '@angular/core';
import { ControlValueAccessor, NG_VALUE_ACCESSOR } from '@angular/forms';
import { cnpjMask, phoneMask } from './format';

@Directive({selector: 'input[appMask]', providers: [{provide: NG_VALUE_ACCESSOR, useExisting: forwardRef(() => Mask), multi: true}]})
export class Mask implements ControlValueAccessor {
  appMask = input.required<'cnpj' | 'phone'>();
  private element = inject<ElementRef<HTMLInputElement>>(ElementRef);
  private change: (value: string) => void = () => {};
  private touched: () => void = () => {};
  private format(value: string): string { return this.appMask() === 'cnpj' ? cnpjMask(value) : phoneMask(value); }
  writeValue(value: string | null): void { this.element.nativeElement.value = this.format(value ?? ''); }
  registerOnChange(fn: (value: string) => void): void { this.change = fn; }
  registerOnTouched(fn: () => void): void { this.touched = fn; }
  setDisabledState(disabled: boolean): void { this.element.nativeElement.disabled = disabled; }
  @HostListener('blur') blur(): void { this.touched(); }
  @HostListener('input') inputChanged(): void {
    const input = this.element.nativeElement;
    const significant = input.value.slice(0, input.selectionStart ?? input.value.length).replace(/[^A-Z0-9]/gi, '').length;
    const formatted = this.format(input.value); input.value = formatted;
    let position = 0; let count = 0;
    while (position < formatted.length && count < significant) { if (/[A-Z0-9]/i.test(formatted[position])) { count++; } position++; }
    input.setSelectionRange(position, position);
    this.change(formatted);
  }
}
