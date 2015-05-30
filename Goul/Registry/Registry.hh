<?hh //strict

namespace Goul\Registry;

newtype RegistryData = shape(
    'setter'    => mixed,
    'data'      => mixed,
    'set'       => bool
);

class Registry
{
    private Map<string, RegistryData> $data = Map{};

    public function contains(string $key): bool
    {
        return $this->data->contains($key);
    }

    public function get(string $key): mixed
    {
        if (!$this->data->contains($key)) {
            throw new \InvalidArgumentException(
                sprintf('Registry - No such key %s', $key)
            );
        }

        $setter = $this->data[$key]['setter'];
        if (is_callable($setter) && $this->data[$key]['set'] === false) {
            if (!is_array($setter)) {
                $this->data[$key]['data'] = (new \ReflectionFunction($this->data[$key]['setter']))->invoke();
            } else {
                $this->data[$key]['data'] = (new \ReflectionMethod($setter[0], $setter[1]))->invoke(
                    $callable->isStatic() ? null : $setter[0],
                    $setter[1]
                );
            }

            $this->data[$key]['set']  = true;
        }

        return $this->data[$key]['data'];
    }

    public function set(string $key, mixed $val): this
    {
        if ($this->data->contains($key)) {
            throw new \RuntimeException(
                sprintf('Registry - Cannot override key %s: key is already set.', $key)
            );
        }

        $isCallable = is_callable($val);
        $this->data[$key] = shape(
            'setter'    => $val,
            'data'      => !$isCallable ? $val : null,
            'set'       => !$isCallable ? true : false
        );

        return $this;
    }

    public function remove(string $key): this
    {
        $this->data->remove($key);

        return $this;
    }
}
