import { cnpjMask, phoneMask, validCnpj } from './format';
describe('Documentos brasileiros', () => {
  it('valida CNPJ numérico e alfanumérico', () => {
    expect(validCnpj('11222333000181')).toBe(true);
    expect(validCnpj('12.ABC.345/01DE-35')).toBe(true);
    expect(validCnpj('11222333000182')).toBe(false);
    expect(validCnpj('00000000000000')).toBe(false);
  });
  it('aplica máscaras e aceita edição parcial', () => {
    expect(cnpjMask('12abc34501de35')).toBe('12.ABC.345/01DE-35');
    expect(cnpjMask('123')).toBe('12.3');
    expect(phoneMask('1133334444')).toBe('(11) 3333-4444');
    expect(phoneMask('11999991234')).toBe('(11) 99999-1234');
    expect(phoneMask('')).toBe('');
  });
});
