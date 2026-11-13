// ITK design system — component props. Global: window.ITK (React 18).
import type * as React from 'react';

export type IconName = 'check' | 'info' | 'success' | 'warning' | 'danger' | 'x' | 'chevronDown' | 'chevronRight' | 'menu' | 'search';
export type Tone = 'info' | 'success' | 'warning' | 'danger';

export interface IconProps { name: IconName; className?: string }

export interface ButtonProps extends Omit<React.ButtonHTMLAttributes<HTMLButtonElement>, 'children'> {
  variant?: 'primary' | 'secondary' | 'ghost' | 'danger';
  size?: 'sm' | 'md' | 'lg';
  icon?: IconName;
  iconRight?: IconName;
  /** Required when the button has an icon and no children. */
  label?: string;
  loading?: boolean;
  href?: string;
  as?: React.ElementType;
  children?: React.ReactNode;
}

interface FieldBase { label: string; hint?: string; error?: string; required?: boolean; id?: string; className?: string }
export interface TextFieldProps extends FieldBase, Omit<React.InputHTMLAttributes<HTMLInputElement>, 'id' | 'className'> {}
export interface SelectOption { value: string; label: string; disabled?: boolean }
export interface SelectProps extends FieldBase, Omit<React.SelectHTMLAttributes<HTMLSelectElement>, 'id' | 'className'> {
  options: Array<string | SelectOption>;
  placeholder?: string;
}

export interface CheckboxProps extends Omit<React.InputHTMLAttributes<HTMLInputElement>, 'type'> { label: React.ReactNode; description?: React.ReactNode }
export interface SwitchProps extends Omit<React.InputHTMLAttributes<HTMLInputElement>, 'type' | 'role'> { label: React.ReactNode }

export interface BadgeProps { tone?: 'neutral' | Tone | 'brand'; icon?: boolean; className?: string; children: React.ReactNode }

export interface AlertProps {
  tone?: Tone;
  title?: React.ReactNode;
  /** Screen-reader prefix; defaults to Info / Berhasil / Perhatian / Galat. */
  toneLabel?: string;
  onDismiss?: () => void;
  dismissLabel?: string;
  className?: string;
  children?: React.ReactNode;
}

export interface CardProps {
  title?: React.ReactNode;
  titleAs?: 'h2' | 'h3' | 'h4';
  eyebrow?: React.ReactNode;
  media?: React.ReactNode;
  footer?: React.ReactNode;
  /** Makes the whole card a single link. */
  href?: string;
  as?: React.ElementType;
  className?: string;
  children?: React.ReactNode;
}

export interface TabItem { value: string; label: string; content: React.ReactNode }
export interface TabsProps { items: TabItem[]; label: string; value?: string; defaultValue?: string; onChange?: (value: string) => void; className?: string }

export interface TableColumn<R> { key: string; header: React.ReactNode; numeric?: boolean; render?: (row: R) => React.ReactNode }
export interface TableProps<R = Record<string, unknown>> { columns: TableColumn<R>[]; rows: R[]; caption: string; rowHeader?: boolean; className?: string }

export interface BreadcrumbItem { label: string; href?: string }
export interface BreadcrumbProps { items: BreadcrumbItem[]; label?: string; className?: string }

export interface NavLink { label: string; href: string; current?: boolean }
export interface SiteHeaderProps {
  logoSrc: string;
  logoSrcDark?: string;
  logoAlt?: string;
  homeHref?: string;
  links: NavLink[];
  actions?: React.ReactNode;
  className?: string;
}

export interface OrnamentProps { src: string; height?: number; align?: 'stretch' | 'center'; className?: string; style?: React.CSSProperties }

export declare const Icon: React.FC<IconProps>;
export declare const Button: React.FC<ButtonProps>;
export declare const TextField: React.FC<TextFieldProps>;
export declare const Select: React.FC<SelectProps>;
export declare const Checkbox: React.FC<CheckboxProps>;
export declare const Switch: React.FC<SwitchProps>;
export declare const Badge: React.FC<BadgeProps>;
export declare const Alert: React.FC<AlertProps>;
export declare const Card: React.FC<CardProps>;
export declare const Tabs: React.FC<TabsProps>;
export declare function Table<R>(props: TableProps<R>): React.ReactElement;
export declare const Breadcrumb: React.FC<BreadcrumbProps>;
export declare const SiteHeader: React.FC<SiteHeaderProps>;
export declare const Ornament: React.FC<OrnamentProps>;

// ---------- Added components ----------
type Node = React.ReactNode;
type Ctl<T> = { value?: T; defaultValue?: T; onChange?: (v: T) => void };
export interface TextProps extends React.HTMLAttributes<HTMLElement> { variant?: 'display-xl' | 'display-l' | 'title-1' | 'title-2' | 'title-3' | 'headline' | 'body-l' | 'body' | 'body-strong' | 'callout' | 'footnote' | 'label' | 'code'; as?: React.ElementType; tone?: 'muted' | 'accent' | 'brand' }
export interface LabelProps extends React.LabelHTMLAttributes<HTMLLabelElement> { required?: boolean }
export interface InputProps extends React.InputHTMLAttributes<HTMLInputElement> { invalid?: boolean }
export interface FieldProps { label: Node; hint?: Node; error?: string; required?: boolean; id?: string; className?: string; children: React.ReactElement }
export interface TextareaProps extends Omit<React.TextareaHTMLAttributes<HTMLTextAreaElement>, 'id'> { label: Node; hint?: Node; error?: string; id?: string; autoGrow?: boolean; showCount?: boolean }
export interface InputGroupProps extends Omit<React.InputHTMLAttributes<HTMLInputElement>, 'prefix'> { label: Node; hint?: Node; error?: string; prefix?: Node | IconName; suffix?: Node | IconName }
export interface InputOTPProps extends Ctl<string> { length?: number; onComplete?: (code: string) => void; label?: Node; hint?: Node; error?: string }
export interface AmountInputProps extends Ctl<number | null> { label: Node; hint?: Node; error?: string; max?: number; currencyLabel?: string; placeholder?: string; name?: string }
export interface KbdProps { keys?: string[]; children?: Node }
export interface SeparatorProps { orientation?: 'horizontal' | 'vertical'; label?: Node }
export interface AspectRatioProps { ratio?: number; children: Node }
export interface AvatarProps { name: string; src?: string; size?: 'sm' | 'md' | 'lg'; status?: 'online' | 'offline' }
export interface MarkerProps { tone?: 'gold' | 'blue'; children: Node }
export interface ButtonGroupProps { label: string; attached?: boolean; children: Node }
export interface ToggleProps { pressed?: boolean; defaultPressed?: boolean; onPressedChange?: (p: boolean) => void; icon?: IconName; label?: string; disabled?: boolean; children?: Node }
export interface ToggleGroupProps extends Ctl<string> { label: string; options: { value: string; label?: string; icon?: IconName; ariaLabel?: string }[]; block?: boolean }
export interface RadioGroupProps extends Ctl<string> { label: Node; options: { value: string; label: Node; description?: Node; disabled?: boolean }[]; orientation?: 'vertical' | 'horizontal'; hint?: Node; error?: string; required?: boolean; name?: string }
export interface SliderProps extends Ctl<number> { label: Node; min?: number; max?: number; step?: number; ticks?: number[]; format?: (v: number) => string; showValue?: boolean; disabled?: boolean }
export interface ChipProps { selected?: boolean; defaultSelected?: boolean; onSelectedChange?: (s: boolean) => void; onRemove?: () => void; icon?: IconName; children: Node }
export interface CollapsibleProps { title: Node; open?: boolean; defaultOpen?: boolean; onOpenChange?: (o: boolean) => void; children: Node }
export interface AccordionProps { items: { value: string; title: Node; content: Node }[]; type?: 'single' | 'multiple'; value?: string | string[] | null; defaultValue?: string | string[]; onChange?: (v: any) => void; headingAs?: 'h2' | 'h3' | 'h4' }
export interface ItemProps { title: Node; subtitle?: Node; leading?: Node | IconName; trailing?: Node; href?: string; onClick?: () => void; chevron?: boolean; current?: boolean }
export interface ItemGroupProps { title?: Node; footer?: Node; children: Node }
export interface EmptyProps { title: Node; icon?: IconName; action?: Node; titleAs?: React.ElementType; children?: Node }
export interface CodeBlockProps { code: string; title?: string; language?: string; lineNumbers?: boolean }
export interface SpinnerProps { size?: number; label?: string }
export interface ProgressProps { value?: number; max?: number; label?: Node; description?: Node; valueText?: string; showValue?: boolean }
export interface SkeletonProps { width?: number | string; height?: number | string; text?: boolean; circle?: boolean; size?: number }
export interface ToastOptions { message: Node; tone?: 'neutral' | Tone; action?: { label: string; onClick: () => void }; duration?: number }
export interface ToasterProps { position?: 'top' | 'bottom' }
export declare function toast(o: ToastOptions | string): string;
interface ModalBase { open?: boolean; onClose?: () => void; inline?: boolean }
export interface DialogProps extends ModalBase { title: Node; description?: Node; footer?: Node; size?: 'sm' | 'md' | 'lg'; children?: Node }
export interface AlertDialogProps { open?: boolean; inline?: boolean; title: Node; message?: Node; confirmLabel: string; cancelLabel?: string | null; destructive?: boolean; onConfirm: () => void; onCancel: () => void }
export interface AlertSheetProps extends ModalBase { title?: Node; message?: Node; actions: { label: string; onSelect?: () => void; destructive?: boolean }[]; cancelLabel?: string }
export interface SheetProps extends ModalBase { title: Node; side?: 'right' | 'left' | 'bottom'; footer?: Node; children: Node }
export interface DrawerProps extends ModalBase { title: Node; detent?: 'medium' | 'large'; defaultDetent?: 'medium' | 'large'; onDetentChange?: (d: 'medium' | 'large') => void; doneLabel?: string; children: Node }
export interface FullscreenMenuProps extends ModalBase { links: { label: string; href: string; description?: string; current?: boolean }[]; logo?: Node; footer?: Node; label?: string }
export interface PopoverProps { trigger: React.ReactElement; title?: Node; label?: string; side?: 'bottom' | 'top'; align?: 'start' | 'end'; open?: boolean; onOpenChange?: (o: boolean) => void; children: Node }
export interface TooltipProps { content: Node; side?: 'top' | 'bottom'; delay?: number; children: React.ReactElement }
export interface HoverCardProps { content: Node; side?: 'bottom' | 'top'; children: React.ReactElement }
export interface MenuItem { label?: string; icon?: IconName; shortcut?: string; onSelect?: () => void; destructive?: boolean; disabled?: boolean; separator?: boolean; heading?: string }
export interface DropdownMenuProps { trigger: React.ReactElement; items: MenuItem[]; label?: string; align?: 'start' | 'end' }
export interface ContextMenuProps { items: MenuItem[]; label: string; children: Node }
export interface MenubarProps { menus: { label: string; items: MenuItem[] }[]; label: string }
export interface ComboboxProps extends Ctl<string> { label: Node; options: Array<string | { value: string; label: string; description?: string; keywords?: string }>; placeholder?: string; hint?: Node; emptyText?: string }
export interface CommandProps { groups: { title: string; items: { label: string; icon?: IconName; keywords?: string; shortcut?: string; onSelect?: () => void }[] }[]; open?: boolean; onOpenChange?: (o: boolean) => void; hotkey?: boolean; placeholder?: string; label?: string }
export interface HeaderProps { title: Node; backHref?: string; onBack?: () => void; backLabel?: string; actions?: Node; largeTitle?: boolean; children?: Node }
export interface TabBarProps { items: { label: string; icon: IconName; href: string; current?: boolean; badge?: number | string }[]; label?: string }
export interface BottomBarProps { label: string; children: Node }
export interface FABProps { icon?: IconName; label?: string; onClick?: () => void; aboveTabBar?: boolean; children?: Node }
export interface PageIndicatorProps { count: number; current: number; onChange?: (i: number) => void; label?: string }
export interface PaginationProps { total: number; page?: number; defaultPage?: number; onChange?: (n: number) => void; href?: (n: number) => string; siblings?: number }
export interface SidebarProps { groups: { title?: string; items: { label: string; href: string; icon?: IconName; current?: boolean; badge?: Node }[] }[]; header?: Node; collapsible?: boolean; collapsed?: boolean; onCollapsedChange?: (c: boolean) => void; label?: string }
export interface NavigationMenuProps { items: Array<{ label: string; href: string; current?: boolean } | { label: string; sections: { title: string; links: { label: string; href: string; description?: string }[] }[] }>; label?: string }
export interface ScrollAreaProps { label: string; maxHeight?: number | string; orientation?: 'vertical' | 'horizontal'; children: Node }
export interface ResizableProps { direction?: 'horizontal' | 'vertical'; size?: number; defaultSize?: number; onSizeChange?: (n: number) => void; min?: number; max?: number; height?: number | string; label?: string; children: [Node, Node] }
export interface ScreenProps { header?: Node; bottom?: Node; mainId?: string; children: Node }
export interface KeyboardScrollViewProps { maxHeight?: number | string; children: Node }
export interface InputAccessoryBarProps { onPrev?: () => void; onNext?: () => void; onDone?: () => void; floating?: boolean; doneLabel?: string; label?: string; children?: Node }
export interface DirectionProps { dir: 'ltr' | 'rtl'; lang?: string; children: Node }
export interface CarouselProps { label: string; peek?: boolean; children: Node }
export interface CalendarProps extends Ctl<Date | null> { min?: Date; max?: Date; isDisabled?: (d: Date) => boolean; marked?: (d: Date) => boolean; weekStartsOn?: number; locale?: string; today?: Date }
export interface WheelPickerProps { label: string; columns: { label: string; options: Array<string | number | { value: string; label: string }>; value: string; onChange: (v: string) => void }[] }
export interface ChartProps { title: string; summary: string; data: Record<string, any>[]; x: string; series: { key: string; label: string }[]; type?: 'bar' | 'line'; height?: number; max?: number; format?: (v: number) => string; xLabel?: string }
export interface BubbleProps { from: 'me' | 'them'; children: Node }
export interface MessageProps { from?: 'me' | 'them'; author?: string; avatar?: string; role?: string; time?: string; status?: string; grouped?: boolean; attachments?: Node; children: Node }
export interface MessageScrollerProps { height?: number | string; label?: string; children: Node }
export interface AttachmentProps { name: string; size?: number; href?: string; progress?: number; error?: string; onRemove?: () => void }
export interface KeypadProps { onKey: (d: string) => void; onDelete: () => void; extraKey?: { value: string; label: string; content?: Node }; disabled?: boolean; label?: string }
export interface PinPadProps { length?: number; onComplete: (pin: string) => void; title?: Node; error?: string; extraKey?: KeypadProps['extraKey']; clearOnComplete?: boolean }
export interface QRScannerProps { onResult: (text: string) => void; prompt?: string; startLabel?: string }
export interface SignaturePadProps { onChange?: (v: string | { typed: string } | null) => void; label?: string }
export interface QuestionnaireProps { questions: { id: string; type: 'single' | 'multiple' | 'scale' | 'text'; title: string; options?: any[]; required?: boolean; description?: string; scaleLabels?: [string, string]; maxLength?: number; errorText?: string }[]; onSubmit?: (answers: Record<string, any>) => void; defaultAnswers?: Record<string, any>; submitLabel?: string; doneTitle?: string; doneText?: string }
export interface TraceProps { steps: { title: Node; status?: 'done' | 'current' | 'pending' | 'error'; time?: string; detail?: Node; collapsible?: boolean; detailTitle?: string }[]; label?: string }
export interface WidgetProps { title: string; icon?: IconName; value?: Node; unit?: string; caption?: Node; updated?: Node; size?: 'small' | 'medium'; brand?: boolean; href?: string; children?: Node }
export declare const haptics: { enabled: boolean; supported(): boolean; play(p: number | number[]): boolean; selection(): boolean; impact(): boolean; success(): boolean; warning(): boolean; error(): boolean };
