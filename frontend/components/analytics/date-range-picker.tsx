'use client';

import { useState } from 'react';
import { Calendar } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { format, subDays, subMonths, startOfMonth, endOfMonth } from 'date-fns';

interface DateRangePickerProps {
  onRangeChange: (startDate: string, endDate: string) => void;
}

export function DateRangePicker({ onRangeChange }: DateRangePickerProps) {
  const [range, setRange] = useState('30days');

  const handleRangeChange = (value: string) => {
    setRange(value);

    const endDate = new Date();
    let startDate: Date;

    switch (value) {
      case '7days':
        startDate = subDays(endDate, 7);
        break;
      case '30days':
        startDate = subDays(endDate, 30);
        break;
      case '90days':
        startDate = subDays(endDate, 90);
        break;
      case 'thisMonth':
        startDate = startOfMonth(endDate);
        break;
      case 'lastMonth':
        startDate = startOfMonth(subMonths(endDate, 1));
        endDate.setTime(endOfMonth(subMonths(new Date(), 1)).getTime());
        break;
      default:
        startDate = subDays(endDate, 30);
    }

    onRangeChange(
      format(startDate, 'yyyy-MM-dd'),
      format(endDate, 'yyyy-MM-dd')
    );
  };

  return (
    <Select value={range} onValueChange={handleRangeChange}>
      <SelectTrigger className="w-[180px]">
        <Calendar className="mr-2 h-4 w-4" />
        <SelectValue />
      </SelectTrigger>
      <SelectContent>
        <SelectItem value="7days">Last 7 days</SelectItem>
        <SelectItem value="30days">Last 30 days</SelectItem>
        <SelectItem value="90days">Last 90 days</SelectItem>
        <SelectItem value="thisMonth">This month</SelectItem>
        <SelectItem value="lastMonth">Last month</SelectItem>
      </SelectContent>
    </Select>
  );
}